## UB — URL Builder

Пакет содержит один класс. Решаемая задача: собирать url со всегда одинаковым порядком параметров, в формате,
идентичном результату отправки формы.

Сборка url происходит в момент приведения объекта к строке. После сборки объект можно переиспользовать,
его состояние не ломается.

Класс подготовлен для наследования. Переопределение нескольких методов позволит генерировать ЧПУ
прозрачно для остальной части проекта. [Пример ЧПУ.](examples/custom-url.md)

### Создание

```php
$ub = new UB($path, $fields, $values);
```

- `$path` — для относительного адреса это путь, для абсолютного — часть строки
  до знака `?`.
- `$fields` — порядок параметров при генерации URL.  Параметры, которых нет в списке,
попадают в конец строки.
- `$values` — все параметры URL в формате \[ключ => значение\].

Все данные доступны для перезаписи через сеттеры.

---
Пример: сборка url из параметров.

```php
print new UB(
    path:   '/catalog/',
    fields: ['price', 'producer_id'],
    values: [
                'producer_id' => [1, 2],
                'sort' => 'popularity',
                'price' => [
                    'max' => 20000,
                ],
            ],
);
```

Результат выполнения:

```text
/catalog/?price%5Bmax%5D=20000&producer_id%5B%5D=1&producer_id%5B%5D=2&sort=popularity
```

---
Пример: создание объекта для текущего запроса.

```php
new UB(
    path:   strtok($_SERVER['REQUEST_URI'], '?'),
    values: $_GET,
)
```

---
Пример: создание объекта из произвольного url.

```php
function ub_from_url(string $url): UB
{
    parse_str(parse_url($url, PHP_URL_QUERY), $values);
    return new UB(
        path:   parse_url($url, PHP_URL_PATH),
        values: $values,
    );
}

$ub = ub_from_url('/catalog/?price%5Bmax%5D=20000&producer_id%5B%5D=1&producer_id%5B%5D=2&sort=popularity');
print $ub->getPath() . "\n";
print json_encode($ub->getAllValues()) . "\n";
```

Результат выполнения:

```text
/catalog/
{"price":{"max":"20000"},"producer_id":["1","2"],"sort":"popularity"}
```

Если будет потребность, можно добавить в класс статическим методом.

## Работа с данными

Все данные объекта доступны для чтения и записи через методы.

Можно изменять сам объект или создавать изменённую копию.
Методы, отвечающие за каждый из способов, отличаются характерными названиями и сигнатурами.
Копирующие методы возвращают копию (`self`), не копирующие — не возвращают ничего (`void`).
Названия копирующих методов начинаются с предлогов `with` и `without`, у не копирующих — начинаются с глагола.

| Метод              | Действие                                                              | Сигнатура                                 |
|--------------------|-----------------------------------------------------------------------|-------------------------------------------|
| ◀️                 |                                                                       |                                           |
| `getPath`          | Получить путь                                                         | `getPath(): string`                       |
| `getFields`        | Получить список полей                                                 | `getFields(): array`                      |
| `hasValue`         | Проверить, определено ли значение параметра                           | `hasValue(string $name): bool`            |
| `getValue`         | Получить значение параметра                                           | `getValue(string $name): mixed`           |
| `getFieldsValues`  | Получить значения полей                                               | `getFieldsValues(): array`                |
| `getAllValues`     | Получить значения всех параметров                                     | `getAllValues(): array`                   |
| ▶️                 |                                                                       |                                           |
| `setPath`          | Изменить путь                                                         | `setPath(string $path): void`             |
| `setValue`         | Установить значение параметра                                         | `setValue(string $name, $value): void`    |
| `unsetValue`       | Удалить значение параметра                                            | `unsetValue(string $name): void`          |
| `appendValue`      | Добавить элемент к массиву значений параметра (аналог операции `[]=`) | `appendValue(string $name, $value): void` |
| ↩️                 |                                                                       |                                           |
| `withPath`         | Изменить путь                                                         | `withPath(string $path): self`            |
| `withFields`       | Изменить список полей                                                 | `withFields(array $fields): self`         |
| `with`             | Установить значение параметра                                         | `with(string $name, $value): self`        |
| `without`          | Удалить значение параметра                                            | `without(string $name): self`             |
| `withValues`       | Установить значения всех параметров                                   | ` withValues(array $values): self`        |
| `withoutValues`    | Удалить значения заданного списка параметров                          | `withoutValues(array $values): self`      |
| `withoutAllValues` | Удалить значения всех параметров                                      | `withoutAllValues(): self`                |

Имплементация `ArrayAccess` и `IteratorAggregate` работает по массиву параметров.
Порядок обхода итератора не определён. Если будет потребность, можно сделать обход по порядку
параметров в адресе.
