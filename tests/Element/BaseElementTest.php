<?php

class BaseElementTest extends PHPUnit_Framework_TestCase
{
    protected $testingClass;

    public function setUp()
    {
        // initBitrixCore();
        $this->testingClass = new \Gb\Element\BaseElement;
    }

    public function testFieldsEmptyArray()
    {
        $fieldsSet = $this->testingClass->getFields();
        $this->assertEquals($fieldsSet, array());
    }

    public function testSetFields()
    {
        $fields = array("name", "long_description");
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
        $compressedFields = array("name", "long_description");
        $this->testingClass->setCompressedFields($compressedFields);
        $compressedFieldsSet = $this->testingClass->getCompressedFields($compressedFields);
        $this->assertEquals($compressedFieldsSet, $compressedFields);
    }

    public function testSetCompressFields()
    {
        $fields = array("name", "long_description");
        $compressedFields = array("long_description");
        $longDescription = str_repeat('1234567890', 100);
        $compressedLongDescription = "789c33343236313533b7b034301c658db24659c3940500fe4ccd15";

        $dataFields = array(
            "name" => "Vasya",
            "long_description" => $longDescription
        );
        $this->testingClass->setFields($fields);
        $this->testingClass->setCompressedFields($compressedFields);
        $compressedData = $this->testingClass->compressFields($dataFields);

        $this->assertEquals($dataFields["name"], $compressedData["name"]);
        $this->assertEquals($compressedLongDescription, $compressedData["long_description"]);

        $uncompressedData = $this->testingClass->uncompressFields($compressedData);
        $this->assertEquals($dataFields["name"], $uncompressedData["name"]);
        $this->assertEquals($dataFields["long_description"], $uncompressedData["long_description"]);
    }


}


?>