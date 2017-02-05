<?php
namespace AkopTests;

use \Akop\Element\QuerySet as QuerySet;

class QuerySetTest extends \PHPUnit_Framework_TestCase
{
    protected $testingClass;
    private $sqlPattern = 'SELECT `b_file`.*' . PHP_EOL . 'FROM `b_file`' . PHP_EOL;

    public function setUp()
    {
        // $this->testingClass = new \Akop\Element\QuerySet;
    }

    public function testConstructor()
    {
        $querySet = new QuerySet('b_file');
        $tableName = new \ReflectionProperty('\Akop\Element\QuerySet', 'tableName');
        $tableName->setAccessible(true);
        $this->assertEquals(
            'b_file',
            $tableName->getValue($querySet)
        );
    }

    public function testAddSelect()
    {
        $querySet = new QuerySet('b_file');
        $property = new \ReflectionProperty('\Akop\Element\QuerySet', 'select');
        $property->setAccessible(true);
        $querySet->addSelect(['FILE_NAME']);
        $this->assertEquals(
            ['FILE_NAME'],
            $property->getValue($querySet)
        );
    }

    public function testAddOrder()
    {
        $querySet = new QuerySet('b_file');
        $property = new \ReflectionProperty('\Akop\Element\QuerySet', 'order');
        $property->setAccessible(true);
        $querySet->addOrder(['FILE_NAME']);
        $this->assertEquals(
            ['FILE_NAME'],
            $property->getValue($querySet)
        );
    }

    public function testAddFilter()
    {
        $querySet = new QuerySet('b_file');
        $property = new \ReflectionProperty('\Akop\Element\QuerySet', 'filter');
        $property->setAccessible(true);
        $querySet->addFilter(['FILE_NAME' => 'xyz.jpg']);
        $this->assertEquals(
            ['FILE_NAME' => 'xyz.jpg'],
            $property->getValue($querySet)
        );
    }

    public function testSetLimit()
    {
        $querySet = new QuerySet('b_file');
        $property = new \ReflectionProperty('\Akop\Element\QuerySet', 'limit');
        $property->setAccessible(true);
        $querySet->setLimit(10);
        $this->assertEquals(
            10,
            $property->getValue($querySet)
        );

        $this->assertEquals(
            $this->sqlPattern . 'LIMIT 10' . PHP_EOL,
            $querySet->getSelectSQL()
        );

        $querySet->setLimit([10, 5]);
        $this->assertEquals(
            [10, 5],
            $property->getValue($querySet)
        );

        $this->assertEquals(
            $this->sqlPattern . 'LIMIT 10,5' . PHP_EOL,
            $querySet->getSelectSQL()
        );
    }

    public function testBuildSelect()
    {
        $querySet = new QuerySet('b_file');
        $method = new \ReflectionMethod('\Akop\Element\QuerySet', 'buildSelect');
        $method->setAccessible(true);
        $querySet->addSelect(['FILE_NAME', 'HEIGHT']);
        $this->assertEquals(
            'SELECT `b_file`.`FILE_NAME`,`b_file`.`HEIGHT`',
            $method->invoke($querySet)
        );
    }

    public function testGetdSelectSQL()
    {
        $querySet = new QuerySet('b_file');
        $querySet->addFilter(['><FILE_SIZE' => [100000, 200000]]);
        $querySet->addSelect(['FILE_NAME', 'HEIGHT']);
        $this->assertEquals(
            'SELECT `b_file`.`FILE_NAME`,`b_file`.`HEIGHT`' . PHP_EOL
                . 'FROM `b_file`' . PHP_EOL
                . "WHERE `b_file`.`FILE_SIZE` BETWEEN '100000' AND '200000'" . PHP_EOL
            ,
            $querySet->getSelectSQL(['FILE_SIZE'])
        );

    }

    public function testBuildSelectEmptySelect()
    {
        $querySet = new QuerySet('b_file');
        $this->assertEquals(
            $this->sqlPattern,
            $querySet->getSelectSQL()
        );

        $method = new \ReflectionMethod('\Akop\Element\QuerySet', 'buildSelect');
        $method->setAccessible(true);
        $this->assertEquals(
            'SELECT `b_file`.*',
            $method->invoke($querySet)
        );
    }

    public function testBuildFrom()
    {
        $querySet = new QuerySet('b_file');
        $method = new \ReflectionMethod('\Akop\Element\QuerySet', 'buildFrom');
        $method->setAccessible(true);
        $this->assertEquals(
            'FROM `b_file`',
            $method->invoke($querySet)
        );
    }

    public function testBuildOrder()
    {
        $querySet = new QuerySet('b_file');
        $method = new \ReflectionMethod('\Akop\Element\QuerySet', 'buildOrder');
        $method->setAccessible(true);
        $querySet->addOrder(['FILE_NAME', 'HEIGHT' => 'desc']);
        $this->assertEquals(
            'ORDER BY `b_file`.`FILE_NAME`,`b_file`.`HEIGHT` desc',
            $method->invoke($querySet)
        );
    }

    public function testBuildFilter()
    {
        $querySet = new QuerySet('b_file');
        $method = new \ReflectionMethod('\Akop\Element\QuerySet', 'buildFilter');
        $method->setAccessible(true);
        $querySet->addFilter(['FILE_NAME' => 'xyz.jpg']);
        $this->assertEquals(
            "WHERE `b_file`.`FILE_NAME` LIKE 'xyz.jpg'",
            $method->invoke($querySet)
        );
    }

    public function testGetOperand()
    {
        $querySet = new QuerySet('b_file');
        $method = new \ReflectionMethod('\Akop\Element\QuerySet', 'getOperand');
        $method->setAccessible(true);
        $this->assertEquals(
            'LIKE',
            $method->invoke($querySet, 'xyz.jpg')
        );

        $this->assertEquals(
            'LIKE',
            $method->invoke($querySet, '123.jpg')
        );

        $this->assertEquals(
            '=',
            $method->invoke($querySet, '123')
        );

        $this->assertEquals(
            'IN',
            $method->invoke($querySet, [1,2,3])
        );
    }

    public function testGetExpression()
    {
        $querySet = new QuerySet('b_file');
        $method = new \ReflectionMethod('\Akop\Element\QuerySet', 'getExpression');
        $method->setAccessible(true);
        $this->assertEquals(
            "`b_file`.`name` LIKE 'Vasya' AND ",
            $method->invoke($querySet, 'name', 'Vasya')
        );
        $this->assertEquals(
            "`b_file`.`salary` = '2000' AND ",
            $method->invoke($querySet, 'salary', 2000)
        );
        $this->assertEquals(
            "`b_file`.`name` IN ('Vasya','Petr','Anna') AND ",
            $method->invoke($querySet, 'name', ['Vasya', 'Petr', 'Anna'])
        );
        $this->assertEquals(
            "`b_file`.`salary` > '2000' AND ",
            $method->invoke($querySet, 'salary', 2000, '>')
        );

        $this->assertEquals(
            "`b_file`.`salary` BETWEEN '2000' AND '3000' AND ",
            $method->invoke($querySet, 'salary', [2000, 3000], '><')
        );

        $this->assertEquals(
            "`b_file`.`date_start` > '03.02.2017' AND ",
            $method->invoke($querySet, 'date_start', '03.02.2017', '>')
        );

    }

    public function testGetAddSQL()
    {
        $querySet = new QuerySet('b_file');
        $this->assertEquals(
            "INSERT INTO `b_file` SET `file_name`='xyz.jpg',`module_id`='main'",
            $querySet->getAddSQL(['file_name' => 'xyz.jpg', 'module_id' => 'main'])
        );
    }

    public function testGetUpdateSQL()
    {
        $querySet = new QuerySet('b_file');
        $this->assertEquals(
            "UPDATE `b_file` SET `file_name`='xyz.jpg',`module_id`='main'"
                . " WHERE `id`=123",
            $querySet->getUpdateSQL(123, ['file_name' => 'xyz.jpg', 'module_id' => 'main'])
        );
    }

    public function testGetUpdateSQLWithPrimaryKeyName()
    {
        $querySet = new QuerySet('b_file', 'ID');
        $this->assertEquals(
            "UPDATE `b_file` SET `file_name`='xyz.jpg',`module_id`='main'"
                . " WHERE `ID`=123",
            $querySet->getUpdateSQL(123, ['file_name' => 'xyz.jpg', 'module_id' => 'main'])
        );
    }

    public function testGetDeleteSQL()
    {
        $querySet = new QuerySet('b_file');
        $this->assertEquals(
            "DELETE FROM `b_file` WHERE `id`=123",
            $querySet->getDeleteSQL(123)
        );
    }

    public function testGetDeleteSQLWithPrimaryKeyName()
    {
        $querySet = new QuerySet('b_file', 'ID');
        $this->assertEquals(
            "DELETE FROM `b_file` WHERE `ID`=123",
            $querySet->getDeleteSQL(123)
        );
    }



}
