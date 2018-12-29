# add
## Описание
```php
add(array $params) : integer;
```
Добавляет строку в модель.
Возвращает `id` вставленной записи или `false` в случае неудачи.

## Пример
```php
add([
  'name' => 'Renault',
  'active' => 'Y',
  'sort' => '200'
]);
```