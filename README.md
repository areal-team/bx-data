# Удобные классы для доступа к данным в 1C-Bitrix одинаковым способом

Вне зависимости от того инфоблок это или highload блок.

* Вам больше не нужно помнить какой ID у инфоблока.  
* Вам больше не
   нужно писать кучу строк для получения элементарных данных из highload
   блока.

## Использование
Для получения списка моделей авто доьсаточно написать:
```php
$models = new \App\Catalog\Model;
$result = $models->getList();
```
В переменной $result вы получаете массив моделей. Больше никаких циклов, никаких GetNext, Fetch и прочего.

Выборка с фильтром, сортировкой и ограничением полей:
```php
$models = new \App\Catalog\Model;
$result = $models->getList([
    "select" => ["id", "name", "brandId", "brandName"],
    "filter" => ["brandId" => 120],
    "order" => ["name" => "asc"],
]);
```

Выборка с фильтром по значению в справочнике, сортировкой и ограничением полей:
```php
$models = new \App\Catalog\Model;
$result = $models->getList([
    "select" => ["id", "name", "brandId", "brandName"],
    "filter" => ["brandName" => "renault"],
    "order" => ["name" => "asc"],
]);
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

composer require aak74/bitrix:dev-master

[Демосайт](http://demo.gbdev.xyz/)
