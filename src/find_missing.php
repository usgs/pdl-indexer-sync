<?php

/**
 * Script to scan 2 product indexes for differences.
 *
 * Outputs counts to STDERR.
 *
 * Outputs list of directories for `reference-host` that should be sent to
 * `compare-host` to sync the 2 instances.
 *
 *
 * Jeremy Fee <jmfee@usgs.gov>
 *
 * Version 1.0 2016-04-05
 *     Initial implementation.
 */


date_default_timezone_set('UTC');

/**
 * This may need to be tuned depending on the number of products in indexes that
 * share an updateTime within the same day.
 *
 * 8GB seems to work for comcat, 2014-11-07 has 1 million products.
 */
ini_set('memory_limit', '8192M');


// parse arguments
$compare_host = null;
$db_name = 'product_index';
$db_pass = null;
$db_user = null;
$delta = 86400;
$endtime = time();
$reference_host = 'localhost';
$reference_directory = '/data/www/data/PDL/indexer_storage';
$starttime = null;

for ($i = 1, $len = count($argv); $i < $len; $i++) {
  $arg = $argv[$i];
  if (strpos($arg, '--compare-host=') === 0) {
    $compare_host = str_replace('--compare-host=', '', $arg);
  } else if (strpos($arg, '--db-name=') === 0) {
    $db_name = str_replace('--db-name=', '', $arg);
  } else if (strpos($arg, '--db-pass=') === 0) {
    $db_pass = str_replace('--db-pass=', '', $arg);
  } else if (strpos($arg, '--db-user=') === 0) {
    $db_user = str_replace('--db-user=', '', $arg);
  } else if (strpos($arg, '--delta=') === 0) {
    $delta = intval(str_replace('--delta=', '', $arg));
  } else if (strpos($arg, '--endtime=') === 0) {
    $endtime = strtotime(str_replace('--endtime=', '', $arg));
  } else if (strpos($arg, '--reference-directory=') === 0) {
    $reference_directory = str_replace('--reference-directory=', '', $arg);
  } else if (strpos($arg, '--reference-host=') === 0) {
    $reference_host = str_replace('--reference-host=', '', $arg);
  } else if (strpos($arg, '--starttime=') === 0) {
    $starttime = strtotime(str_replace('--starttime=', '', $arg));
  } else {
    echo 'Unexpected argument "' . $arg . '"' . PHP_EOL;
    printUsage();
  }
}

if ($compare_host === null ||
    $db_name === null ||
    $db_pass === null ||
    $db_user === null ||
    $delta <= 0 ||
    $endtime === null ||
    $reference_directory === null ||
    $reference_host === null ||
    $starttime === null) {
  printUsage();
}


// connect to databases and prepare queries
$reference_dbh = new PDO(
    'mysql:host=' . $reference_host . ';dbname=' . $db_name,
    $db_user,
    $db_pass);
$reference_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$reference_count = $reference_dbh->prepare('
    select count(*)
    from productSummary
    where
    updateTime between ? and ?
');
$reference_query = $reference_dbh->prepare('
    select source, type, code, updateTime
    from productSummary
    where
    updateTime between ? and ?
');

$compare_dbh = new PDO(
    'mysql:host=' . $compare_host . ';dbname=' . $db_name,
    $db_user,
    $db_pass);
$compare_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$compare_count = $compare_dbh->prepare('
    select count(*)
    from productSummary
    where
    updateTime between ? and ?
');
$compare_query = $compare_dbh->prepare('
    select source, type, code, updateTime
    from productSummary
    where
    updateTime between ? and ?
');


$stderr = fopen('php://stderr', 'w+');
$time = $starttime;
while ($time < $endtime) {
  $loop_start = $time;
  $loop_end = min($time + $delta, $endtime);
  $time = $time + $delta;

  // get products
  $compare_query->execute(array($loop_start . '000', $loop_end . '000'));
  $compare = $compare_query->fetchAll();
  $compare_query->closeCursor();
  $reference_query->execute(array($loop_start . '000', $loop_end . '000'));
  $reference = $reference_query->fetchAll();
  $reference_query->closeCursor();

  // extract ids
  $compare = getIds($compare);
  $reference = getIds($reference);

  // compare
  $compareMissing = findMissing($reference, $compare);
  $numMissingCompare = count($compareMissing);
  $referenceMissing = findMissing($compare, $reference);
  $numMissingReference = count($referenceMissing);

  // output comparison
  fwrite($stderr,
    gmdate('c', $loop_start) . ' to ' . gmdate('c', $loop_end) .
      ' ' . count($reference) . ' products' .
      PHP_EOL);

  if ($numMissingReference !== 0) {
    fwrite($stderr,
        "\tMISSING " . $numMissingReference . ' on ' . $reference_host .
        PHP_EOL);
  }

  if ($numMissingCompare !== 0) {
    fwrite($stderr,
        "\tMISSING " . $numMissingCompare . ' on ' . $compare_host .
        ' (output to stdout)' .
        PHP_EOL);

    // output products to be sent to stdout
    foreach ($compareMissing as $key => $value) {
      echo $key . PHP_EOL;
    }
  }
}
fclose($stderr);


// free database
$compare_count = null;
$compare_query = null;
$compare_dbh = null;

$reference_count = null;
$reference_query = null;
$reference_dbh = null;





/**
 * Convert array of products to an associative array, keyed by a unique id.
 *
 * @param $arr {Array<Array>}
 *     array of products.
 * @return {Array<String => Array>}
 *     array keyed product directory.
 */
function getIds ($arr) {
  global $reference_directory;

  $ids = array();

  foreach ($arr as $value) {
    $key = implode(DIRECTORY_SEPARATOR, array(
          $reference_directory,
          $value['type'],
          $value['code'],
          $value['source'],
          $value['updateTime']));
    $ids[$key] = $value;
  }

  return $ids;
}

/**
 * Compare two arrays, returning an array of products that are missing.
 *
 * @param $reference {Array<String => ?>}
 *     the reference set of products.
 * @param $test {Array<String => ?>}
 *     the array to check for missing products.
 * @return {Array<String => ?>}
 *     array containing keys/values from $reference that do not appear in $test.
 *     only keys are used for comparison.
 */
function findMissing ($reference, $test) {
  $missing = array();

  foreach ($reference as $key => $value) {
    if (!isset($test[$key])) {
      $missing[$key] = $value;
    }
  }

  return $missing;
}

/**
 * Print usage, then exit.
 */
function printUsage () {
  global $argv;

  $stderr = fopen('php://stderr', 'w+');
  fwrite($stderr, implode(PHP_EOL, array(
      'Usage:',
      '',
      '    php ' . $argv[0] .
          ' --compare-host=COMPARE_HOST' .
          ' [--db-name=product_index]' .
          ' --db-pass=DBPASS' .
          ' --db-user=DBUSER' .
          ' [--delta=86400]'
          ' [--endtime=NOW]' .
          ' [--reference-directory=/data/www/data/PDL/indexer_storage]'
          ' [--reference-host=localhost]' .
          ' --starttime=STARTTIME',
      '',
      '--compare-host',
      '    The host to be checked.',
      '--db-name',
      '    The database name on both hosts.',
      '    Default "product_index"',
      '--db-pass',
      '    Password for database user on both hosts',
      '--db-user',
      '    User with read access to database on both hosts',
      '--delta',
      '    Interval in seconds to check at one time.',
      '    Default 86400.',
      '    Must be greater than 0.',
      '--endtime',
      '    Time of last product to compare.',
      '    Default is current time.',
      '    Anything `strtotime` supports (e.g. YYYY-MM-DD).'
      '--reference-directory',
      '    Directory where products are located.',
      '--reference-host',
      '    The host with products that should exist.',
      '    Default "localhost".',
      '--starttime',
      '    Time of the first product to compare.',
      '    Anything `strtotime` supports (e.g. YYYY-MM-DD).'
      // add extra blank line
      '')) . PHP_EOL);
  fclose($stderr);
  exit(1);
}
