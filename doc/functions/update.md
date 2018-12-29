# update
## Описание
```php
update($id, array $params) : integer;
```
Обновляет строку в модели.
Возвращает `id` измененной записи.

## Пример
```php
update(
  1,
  [
    'name' => 'Renault',
    'active' => 'Y',
    'sort' => '200'
  ]
);
```