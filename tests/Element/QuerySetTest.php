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
            $querySet->toSQL()
        );

        $querySet->setLimit([10, 5]);
        $this->assertEquals(
            [10, 5],
            $property->getValue($querySet)
        );

        $this->assertEquals(
            $this->sqlPattern . 'LIMIT 10,5' . PHP_EOL,
            $querySet->toSQL()
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

        $this->assertEquals(
            'SELECT `b_file`.`FILE_NAME`,`b_file`.`HEIGHT`' . PHP_EOL . 'FROM `b_file`' . PHP_EOL,
            $querySet->toSQL()
        );

    }

    public function testBuildSelectEmptySelect()
    {
        $querySet = new QuerySet('b_file');
        $this->assertEquals(
            $this->sqlPattern,
            $querySet->toSQL()
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
            $method->invoke($querySet, 'FILE_NAME', 'xyz.jpg')
        );

        $this->assertEquals(
            'LIKE',
            $method->invoke($querySet, 'FILE_NAME', '123.jpg')
        );

        $this->assertEquals(
            '=',
            $method->invoke($querySet, 'ID', '123')
        );

        // $this->assertEquals(
        //     '>',
        //     $method->invoke($querySet, '>ID', '123')
        // );
    }

}
