# pdl-indexer-sync
Compare and detect differences between PDL indexer instances


This project provides a script `src/find_missing.php` that can be used to
detect differences between two separate PDL indexer instances; and combined
with ProductClient.jar from http://github.com/usgs/pdl synchronize the
instances.


## Example
To synchronize between two servers, `SERVER1` and `SERVER2`.

On `SERVER1`, generate the list of products that are missing from `SERVER2`:
```bash
php src/find_missing.php \
    --compare-host=SERVER2 \
    --db-pass=PASS \
    --db-user=USER \
    --starttime=2000-01-01 \
    > missing_products.txt
```

Still on `SERVER1`, use list of products with the PDL ProductResender to send
products to `SERVER2`.
```bash
cat missing_products.txt | java -cp ProductClient.jar \
    gov.usgs.earthquake.distribution.ProductResender \
    --batch \
    --informat=directory \
    --servers=SERVER2:11235
```


## Example script output
- Paths for missing products are output to STDOUT.
- Times and other information are output to STDERR.

```
2016-02-05T00:00:00+00:00 to 2016-02-06T00:00:00+00:00 5319 products
2016-02-06T00:00:00+00:00 to 2016-02-07T00:00:00+00:00 5258 products
2016-02-07T00:00:00+00:00 to 2016-02-08T00:00:00+00:00 3805 products
2016-02-08T00:00:00+00:00 to 2016-02-09T00:00:00+00:00 5356 products
	MISSING 2 on SERVER1 (output to stdout)
/data/www/data/PDL/indexer_storage/dyfi/us20002avh/us/1454961664561
/data/www/data/PDL/indexer_storage/dyfi/us20002avh/us/1454961685794
2016-02-09T00:00:00+00:00 to 2016-02-10T00:00:00+00:00 5149 products
	MISSING 33 on SERVER1 (output to stdout)
/data/www/data/PDL/indexer_storage/origin/us10003zcb/us/1454989364040
/data/www/data/PDL/indexer_storage/phase-data/us10003zcb/us/1454989364040
/data/www/data/PDL/indexer_storage/dyfi/nc72507751/us/1454993394720
/data/www/data/PDL/indexer_storage/dyfi/nc72507751/us/1454993408523
/data/www/data/PDL/indexer_storage/dyfi/nc72507761/us/1454993414193
/data/www/data/PDL/indexer_storage/dyfi/nc72507761/us/1454993419400
/data/www/data/PDL/indexer_storage/origin/us10003yjs/us/1454997052040
/data/www/data/PDL/indexer_storage/phase-data/us10003yjs/us/1454997052040
/data/www/data/PDL/indexer_storage/origin/us10003yjs/us/1454997075040
/data/www/data/PDL/indexer_storage/phase-data/us10003yjs/us/1454997075040
/data/www/data/PDL/indexer_storage/origin/us10003yyn/us/1455001714040
/data/www/data/PDL/indexer_storage/phase-data/us10003yyn/us/1455001714040
/data/www/data/PDL/indexer_storage/origin/us10003yhc/us/1455002483040
/data/www/data/PDL/indexer_storage/phase-data/us10003yhc/us/1455002483040
/data/www/data/PDL/indexer_storage/moment-tensor/us_10003yhc_mwr/us/1455002483040
/data/www/data/PDL/indexer_storage/origin/us10003ywp/us/1455002680040
/data/www/data/PDL/indexer_storage/phase-data/us10003ywp/us/1455002680040
/data/www/data/PDL/indexer_storage/moment-tensor/us_10003ywp_mww/us/1455002680040
/data/www/data/PDL/indexer_storage/moment-tensor/us_10003ywp_mwc_gcmt/us/1455002680040
/data/www/data/PDL/indexer_storage/moment-tensor/us_10003ywp_mwb/us/1455002680040
/data/www/data/PDL/indexer_storage/losspager/us10003yhc/us/1455002763367
/data/www/data/PDL/indexer_storage/losspager/us10003ywp/us/1455003073434
/data/www/data/PDL/indexer_storage/origin/nm60070727/nm/1455025963480
/data/www/data/PDL/indexer_storage/phase-data/nm60070727/nm/1455025963480
/data/www/data/PDL/indexer_storage/origin/se60004608/se/1455026003330
/data/www/data/PDL/indexer_storage/phase-data/se60004608/se/1455026003330
/data/www/data/PDL/indexer_storage/origin/se60009408/se/1455026009730
/data/www/data/PDL/indexer_storage/phase-data/se60009408/se/1455026009730
/data/www/data/PDL/indexer_storage/origin/us10003yp4/us/1455027379040
/data/www/data/PDL/indexer_storage/phase-data/us10003yp4/us/1455027379040
/data/www/data/PDL/indexer_storage/moment-tensor/us_10003yp4_mwr/us/1455027379040
/data/www/data/PDL/indexer_storage/origin/us10003ypf/us/1455027684040
/data/www/data/PDL/indexer_storage/phase-data/us10003ypf/us/1455027684040
2016-02-10T00:00:00+00:00 to 2016-02-11T00:00:00+00:00 4863 products
2016-02-11T00:00:00+00:00 to 2016-02-12T00:00:00+00:00 4931 products
2016-02-12T00:00:00+00:00 to 2016-02-13T00:00:00+00:00 3660 products
2016-02-13T00:00:00+00:00 to 2016-02-14T00:00:00+00:00 3881 products
2016-02-14T00:00:00+00:00 to 2016-02-15T00:00:00+00:00 3756 products
2016-02-15T00:00:00+00:00 to 2016-02-16T00:00:00+00:00 7516 products
2016-02-16T00:00:00+00:00 to 2016-02-17T00:00:00+00:00 7206 products
2016-02-17T00:00:00+00:00 to 2016-02-18T00:00:00+00:00 7594 products
```
