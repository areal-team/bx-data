<?php
namespace AkopTests;

class BaseElementTest extends \PHPUnit_Framework_TestCase
{
    protected $testingClass;

    public function setUp()
    {
        // initBitrixCore();
        $this->testingClass = new \Akop\Element\BaseElement;
    }

    public function testFieldsEmptyArray()
    {
        $fieldsSet = $this->testingClass->getFields();
        $this->assertEquals($fieldsSet, array());
    }

    public function testSetFields()
    {
        $fields = array("name", "data");
        $this->testingClass->setFields($fields);
        $fieldsSet = $this->testingClass->getFields($fields);
        $this->assertEquals($fieldsSet, $fields);
    }

    public function testCompressedFieldsEmptyArray()
    {
        $compressedFieldsSet = $this->testingClass->getCompressedFields();
        $this->assertEquals($compressedFieldsSet, array());
    }

    public function testSetCompressedFields()
    {
        $compressedFields = array("name", "data");
        $this->testingClass->setCompressedFields($compressedFields);
        $compressedFieldsSet = $this->testingClass->getCompressedFields($compressedFields);
        $this->assertEquals($compressedFieldsSet, $compressedFields);
    }

    public function testSetCompressFields()
    {
        $fields = array("name", "data");
        $compressedFields = array("data");
        $data = str_repeat('1234567890', 100);
        $compressedSample = "789c33343236313533b7b034301c658db24659c3940500fe4ccd15";

        $dataFields = array(
            "name" => "Vasya",
            "data" => $data
        );
        $this->testingClass->setFields($fields);
        $this->testingClass->setCompressedFields($compressedFields);
        $compressedData = $this->testingClass->compressFields($dataFields);

        $this->assertEquals($dataFields["name"], $compressedData["name"]);
        $this->assertEquals($compressedSample, $compressedData["data"]);

        $uncompressedData = $this->testingClass->uncompressFields($compressedData);
        $this->assertEquals($dataFields["name"], $uncompressedData["name"]);
        $this->assertEquals($dataFields["data"], $uncompressedData["data"]);
    }

    public function testGetCleanFieldNameWithoutPrefix()
    {
        $this->testingClass->setFields(["fieldName"]);
        $this->assertEquals(
            ["name" => "fieldName", "prefix" => ""],
            $this->testingClass->getCleanFieldName("fieldName")
        );
    }

    public function testGetCleanFieldNameWrongFieldName()
    {
        $this->testingClass->setFields(["fieldName"]);
        $this->assertEquals(
            false,
            $this->testingClass->getCleanFieldName("wrongFieldName")
        );
    }

    public function testGetCleanFieldNameWrongFieldNameWithPrefix()
    {
        $this->testingClass->setFields(["fieldName"]);
        $this->assertEquals(
            false,
            $this->testingClass->getCleanFieldName("!=wrongFieldName")
        );
    }

    public function testGetCleanFieldName()
    {
        $this->testingClass->setFields(["fieldName"]);

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
}
