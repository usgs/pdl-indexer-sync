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
