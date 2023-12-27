```php
function ub_from_url(string $url): UB
{
    parse_str(parse_url($url, PHP_URL_QUERY), $values);
    return new UB(
        path:   parse_url($url, PHP_URL_PATH),
        values: $values,
    );
}
```

```php
$ub = ub_from_url('/catalog/?price%5Bmax%5D=20000&producer_id%5B%5D=1&producer_id%5B%5D=2&sort=popularity');
print $ub->getPath() . "\n";
print json_encode($ub->getAllValues()) . "\n";
```

```text
/catalog/
{"price":{"max":"20000"},"producer_id":["1","2"],"sort":"popularity"}
```
