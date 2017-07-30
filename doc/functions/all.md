# Функции

## [getList](getList.md)
```php
getList(array $params = [])
```

Возвращает **массив строк** в соответствии с переданным параметром. Единственный параметр функции $params не является обязательным.

## [getRow](getRow.md)
```php
getRow(array $params = [])
```
Возвращает **одну строку** в соответствии с переданным параметром.

## add
```php
add(array $params)
```
Добавляет строку.

## update
```php
update($primaryKey, array $params)
```
Обновляетвляет строку.

## upsert
```php
upsert(array $filter, array $params)
```
Обновляет строку если найдена строка в соответствии с фильтром. Если такой строки нет, то добавляет строку.
