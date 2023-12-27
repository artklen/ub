```php

class CatalogUB extends UB
{
    public const PATH = '/catalog/';

    // пример, где есть соответствие значений и псевдонимов один к одному
    public const PRODUCER_VALUES = [
        'producer1' => '1',
        'producer2' => '2',
    ];
    public const PRODUCER_SLUGS = [
        1 => 'producer1',
        2 => 'producer2',
    ];

    // пример, где псевдоним заменяется на сложное значение
    public const PRICE_VALUES = [
        '0-100' => ['max' => '100'],
        '100-500' => ['min' => '100', 'max' => '500'],
        '500-' => ['min' => '500'],
    ];

    public static function createFromUrl(string $url): ?self
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $values);
        $result = new self(self::PATH, ['price', 'producer_id'], $values);

        #region разделение пути на части
        $path = parse_url($url, PHP_URL_PATH);
        if (str_starts_with($path, self::PATH)) {
            $path = substr($path, strlen(self::PATH));
        }
        $path = ltrim($path, '/');
        if ($path === '') {
            return $result;
        }
        $parts = explode('/', $path);
        #endregion

        #region часть producer_id
        $part = array_shift($parts);
        if ($part === null) {
            // если параметр не обязательный, иначе было бы return null
            return $result;
        }
        if (! isset(self::PRODUCER_VALUES[$part])) {
            return null;
        }
        // параметр дополняется
        $result->appendValue('producer_id', self::PRODUCER_VALUES[$part]);
        #endregion

        #region часть price
        $part = array_shift($parts);
        if ($part === null) {
            // если параметр не обязательный, иначе было бы return null
            return $result;
        }
        if (! isset(self::PRICE_VALUES[$part])) {
            return null;
        }
        // параметр перезаписывается
        $result->setValue('price', self::PRICE_VALUES[$part]);
        #endregion

        // что делать, если остались неразобранные части
        if ($parts !== []) {
            return null;
        }

        return $result;
    }

    public function __toString(): string
    {
        $url = $this->getPath();
        if (mb_substr($url, -1) === '/') {
            $url = mb_substr($url, 0, -1);
        }
        $needSlash = true;

        $values = $this->getAllValues();

        $value = null;
        #region если есть ровно один producer_id, извлекаем его и удаляем из параметров
        if (isset($values['producer_id'])) {
            if (! is_array($values['producer_id'])) {
                $value = $values['producer_id'];
                unset($values['producer_id']);
            } elseif (count($values['producer_id']) === 1) {
                $value = reset($values['producer_id']);
                unset($values['producer_id']);
            }
        }
        #endregion
        if ($value !== null && isset(self::PRODUCER_SLUGS[$value])) {
            $url .= '/' . urlencode(self::PRODUCER_SLUGS[$value]);
            $needSlash = false;
            // если бы параметр был обязательным, дальнейший разбор шёл бы внутри этой ветки
        }

        if (isset($values['price'])) {
            $slug = array_search($values['price'], self::PRICE_VALUES, false); // без учёта порядка
            if ($slug !== false) {
                unset($values['price']);
                $url .= '/' . urlencode($slug);
                $needSlash = false;
                // если бы параметр был обязательным, дальнейший разбор шёл бы внутри этой ветки
            }
        }

        if ($needSlash) {
            $url .= '/';
        }

        return (string) new UB($url, $this->fields, $values);
    }
}
```

```php
$ub = CatalogUB::createFromUrl('/catalog/producer1/100-500?sort=popularity');
print $ub->getPath() . "\n\n";
print json_encode($ub->getAllValues()) . "\n\n";
print $ub . "\n\n";
```

```text
/catalog/

{"sort":"popularity","producer_id":["1"],"price":{"min":"100","max":"500"}}

/catalog/producer1/100-500?sort=popularity

```