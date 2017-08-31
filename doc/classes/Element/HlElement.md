# HlElement

Предназначен для доступа к элементам highload блоков

В классе реализовано получение данных из справочников (других highload блоков). Данные будут получены из БД с помощью одного запроса.

## Типичный наследник класса

```PHP
<?
namespace App\Model;

class Model extends \Akop\Element\HlElement
{
	protected $entityName = 'Model';
}
```

## Типичный getList
```PHP
<?php
$models = new \App\Model\Model;

$result = $models->getList([
  'select' => ['id', 'name', 'brandId', 'brandName'],
  'filter' => ['brandId' => 120],
  'order' => ['name' => 'asc'],
]);
```

## Формируется запрос вида:

```SQL
SELECT
  `model`.`ID` AS `ID`,
  `model`.`UF_NAME` AS `UF_NAME`,
  `model`.`UF_BRAND` AS `UF_BRAND`,
  `model_brandname_`.`UF_NAME` AS `brandName`
FROM `b_hlbd_auto_model` `model`
LEFT JOIN `b_hlbd_auto_brand` `model_brandname_` ON `model`.`UF_BRAND` = `model_brandname_`.`ID`
WHERE `model`.`UF_BRAND` = 120
ORDER BY `model`.`UF_NAME` ASC
```
