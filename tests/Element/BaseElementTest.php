<?php
namespace AkopTests;

class BaseElementTest extends \PHPUnit_Framework_TestCase
{
    protected $testingClass;

    public function setUp()
    {
        $this->testingClass = new \Akop\Element\BaseElement;
    }

    public function testGetRow()
    {
        $user1 = ['id' => 150, 'name' => 'Vasya', 'salary' => '2000'];
        $user2 = ['id' => 200, 'name' => 'Petya', 'salary' => '1800'];
        $data = [
            150 => $user1,
            200 => $user2,
        ];

        $stub = $this->getMock('\Akop\Element\BaseElement', ['getList']);
        $stub->method('getList')
             ->willReturn($data);

        $this->assertEquals($user1, $stub->getRow());
    }


    public function testFieldsNotAnEmptyArray()
    {
        $this->assertEquals(["ID"], $this->testingClass->getFields());
    }

    public function testSetFields()
    {
        $fields = ["name", "data"];
        $this->testingClass->setFields($fields);
        $this->assertEquals($fields, $this->testingClass->getFields($fields));
    }

    public function testCompressedFieldsEmptyArray()
    {
        $this->assertEquals([], $this->testingClass->getCompressedFields());
    }

    public function testSetCompressedFields()
    {
        $compressedFields = array("name", "data");
        $this->testingClass->setCompressedFields($compressedFields);
        $this->assertEquals($compressedFields, $this->testingClass->getCompressedFields($compressedFields));
    }

    /**
     * Тестирует компрессию и декомпрессию полей
     */
    public function testSetCompressFields()
    {
        $methodCompressFields = new \ReflectionMethod('\Akop\Element\BaseElement', 'compressFields');
        $methodCompressFields->setAccessible(true);
        $methodUncompressField = new \ReflectionMethod('\Akop\Element\BaseElement', 'uncompressField');
        $methodUncompressField->setAccessible(true);

        $fields = array("name", "data");
        $compressedFields = array("data");
        $data = str_repeat('1234567890', 100);
        $compressedReference = "789c33343236313533b7b034301c658db24659c3940500fe4ccd15";

        $dataFields = array(
            "name" => "Vasya",
            "data" => $data
        );
        $this->testingClass->setFields($fields);
        $this->testingClass->setCompressedFields($compressedFields);
        // $compressedData = $this->testingClass->compressFields();
        $compressedData = $methodCompressFields->invoke($this->testingClass, $dataFields);

        $this->assertEquals($dataFields["name"], $compressedData["name"]);
        $this->assertEquals($compressedReference, $compressedData["data"]);

        $this->assertEquals(
            $dataFields["name"],
            $methodUncompressField->invoke($this->testingClass, "name", $compressedData["name"])
        );
        $this->assertEquals(
            $dataFields["data"],
            $methodUncompressField->invoke($this->testingClass, "data", $compressedData["data"])
        );
    }

    public function testGetCleanFieldNameWithoutPrefix()
    {
        $this->testingClass->setFields(["fieldName" => "UF_FIELD_NAME"]);
        $this->assertEquals(
            ["name" => "fieldName", "prefix" => ""],
            $this->testingClass->getCleanFieldName("fieldName")
        );
    }

    public function testGetCleanFieldNameWrongFieldName()
    {
        $this->testingClass->setFields(["fieldName" => "UF_FIELD_NAME"]);
        $this->assertEquals(
            false,
            $this->testingClass->getCleanFieldName("wrongFieldName")
        );
    }

    public function testGetCleanFieldNameWrongFieldNameWithPrefix()
    {
        $this->testingClass->setFields(["fieldName" => "UF_FIELD_NAME"]);
        $this->assertEquals(
            false,
            $this->testingClass->getCleanFieldName("!=wrongFieldName")
        );
    }

    public function testGetCleanFieldName()
    {
        $this->testingClass->setFields(["fieldName" => "UF_FIELD_NAME"]);

        $filterPrefixes = ["=", "%", "?", ">", "<", "!", "@",
            "!=", "!%", "><", ">=", "<=", "=%", "%=", "!@",
            "!><", "!=%", "!%="
        ];

        foreach ($filterPrefixes as $filterPrefix) {
            $this->assertEquals(
                ["name" => "fieldName", "prefix" => $filterPrefix],
                $this->testingClass->getCleanFieldName($filterPrefix . "fieldName")
            );
        }
    }

    public function testUpdateParamsSelectSimple()
    {
        $property = new \ReflectionProperty('\Akop\Element\BaseElement', 'params');
        $property->setAccessible(true);

        $this->testingClass->getList(['select' => ['NAME']]);
        $params = $property->getValue($this->testingClass);
        $this->assertEquals(['NAME', 'ID'], $params['select']);
    }

    public function testUpdateParamsSelectComplex()
    {
        $brandField = [
            'name' => 'UF_NAME',
            'data_type' => '\Brand',
            'reference' => ['=this.UF_BRAND' => 'ref.ID']
        ];

        $modelField = [
            'name' => 'UF_NAME',
            'data_type' => '\Model',
            'reference' => ['=this.UF_MODEL' => 'ref.ID']
        ];
        $fields = [
            'id' => 'ID',
            'body' => 'UF_NAME',
            'brandName' => $brandField,
            'brandId' => 'UF_BRAND',
            'modelName' => $modelField,
            'modelId' => 'UF_MODEL'
        ];
        $paramsReference = [
            'select' => [
                'ID',
                'UF_NAME',
                'brandName' => 'brandName_.UF_NAME',
                'UF_BRAND',
                'modelName' => 'modelName_.UF_NAME',
                'UF_MODEL',
            ],
            'runtime' => [
                'brandName_' => $brandField,
                'modelName_' => $modelField,
            ],
        ];

        $method = new \ReflectionMethod('\Akop\Element\BaseElement', 'updateParamsSelect');
        $method->setAccessible(true);
        $property = new \ReflectionProperty('\Akop\Element\BaseElement', 'params');
        $property->setAccessible(true);

        $element = new \Akop\Element\BaseElement;
        $element->setFields($fields);
        $method->invoke($element);
        // print_r($property->getValue($element));
        $this->assertEquals($paramsReference, $property->getValue($element));
    }
}
