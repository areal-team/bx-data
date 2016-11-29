# Удобные классы для доступа к данным в 1C-Bitrix одинаковым способом
[![Build Status](https://api.travis-ci.org/aak74/bx-data.svg?branch=master)](https://travis-ci.org/aak74/bx-data)
[![Latest Stable Version](https://poser.pugx.org/aak74/bx-data/v/stable)](https://packagist.org/packages/aak74/bx-data)
[![Latest Unstable Version](https://poser.pugx.org/aak74/bx-data/v/unstable)](https://packagist.org/packages/aak74/bx-data)
[![License](https://poser.pugx.org/aak74/bx-data/license)](https://packagist.org/packages/aak74/bx-data)

Вне зависимости от того инфоблок это или highload блок.

* Вам больше не нужно помнить какой ID у инфоблока.
* Вам больше не
   нужно писать кучу строк для получения элементарных данных из highload
   блока.

## Использование
Для получения списка моделей авто достаточно написать:
```php
$models = new \App\Catalog\Model;
$result = $models->getList();
```
В переменной $result вы получаете массив моделей. Больше никаких циклов, никаких GetNext, Fetch и прочего.

### Выборка с фильтром, сортировкой и ограничением полей:
```php
$models = new \App\Catalog\Model;
$result = $models->getList([
    "select" => ["id", "name", "brandId", "brandName"],
    "filter" => ["brandId" => 120],
    "order" => ["name" => "asc"],
]);
```
#### В БД уйдет один запрос вида:
```sql
SELECT
    `model`.`ID` AS `ID`,
    `model`.`UF_NAME` AS `UF_NAME`,
    `model`.`UF_BRAND` AS `UF_BRAND`,
    `model_brandname_`.`UF_NAME` AS `brandName`
FROM `b_hlbd_auto_model` `model`
LEFT JOIN `b_hlbd_auto_brand` `model_brandname_` ON `model`.`UF_BRAND` = `model_brandname_`.`ID`
WHERE `model`.`UF_BRAND` = 120
AND (`model`.`UF_DELETED` IS NULL OR `model`.`UF_DELETED` = 0)
ORDER BY `model`.`UF_NAME` ASC
```

### Выборка с фильтром по значению в справочнике, сортировкой и ограничением полей:
```php
$models = new \App\Catalog\Model;
$result = $models->getList([
    "select" => ["id", "name", "brandId", "brandName"],
    "filter" => ["brandName" => "renault"],
    "order" => ["name" => "asc"],
]);
```
#### В БД уйдет один запрос вида:
```sql
SELECT 
    `model`.`ID` AS `ID`,
    `model`.`UF_NAME` AS `UF_NAME`,
    `model`.`UF_BRAND` AS `UF_BRAND`,
    `model_brandname_`.`UF_NAME` AS `brandName`
FROM `b_hlbd_auto_model` `model` 
LEFT JOIN `b_hlbd_auto_brand` `model_brandname_` ON `model`.`UF_BRAND` = `model_brandname_`.`ID`
WHERE UPPER(`model_brandname_`.`UF_NAME`) like upper('renault')
AND (`model`.`UF_DELETED` IS NULL OR `model`.`UF_DELETED` = 0)
```

Вы можете сказать:
Highload блоки могут сделать тоже самое. Пусть и несколько более многословно.

Да конечно. Только стоит вспомнить сколько нужно написать в классе описания сущности представляемым highload блоком. И сразу не хочется этого делать.
Сравните что нужно написать сейчас:

```php
namespace App\Catalog;

class Model extends \Akop\Element\HlElement
{
    protected $entityName = 'Model';
}
```

Это весь текст класса. **ВЕСЬ**. Все остальное сделано за вас.

##  Установка

Установка происходит стандартным для [composer](http://getcomposer.org/) способом:

```
composer require aak74/bx-data
```

[Демосайт](http://demo.gbdev.xyz/)
