<?php
namespace AkopTests;

class FieldPrefixTest extends \PHPUnit_Framework_TestCase
{
    protected $testingClass;

    public function setUp()
    {
    }

    public function testGetFieldsPrefixesIsArray()
    {
        $fieldPrefix = new \Akop\Element\FieldPrefix(["fieldName"]);

        $this->assertInternalType(
            'array',
            $fieldPrefix->getFieldsPrefixes(["!fieldName"])
        );
    }

    public function testGetFieldsPrefixesInputAndOutputLengthEqual()
    {
        $fieldPrefix = new \Akop\Element\FieldPrefix(["fieldName", "fieldName2"]);

        $this->assertEquals(
            2,
            count($fieldPrefix->getFieldsPrefixes(["!fieldName", "<>fieldName2"]))
        );
    }

    public function testGetFieldWithPrefix()
    {
        $fieldPrefix = new \Akop\Element\FieldPrefix(["fieldName"]);

        $filterPrefixes = ["=", "%", "?", ">", "<", "!", "@",
            "!=", "!%", "><", ">=", "<=", "=%", "%=", "!@",
            "!><", "!=%", "!%="
        ];

        foreach ($filterPrefixes as $filterPrefix) {
            $this->assertEquals(
                ["name" => "fieldName", "prefix" => $filterPrefix],
                $fieldPrefix->getFieldWithPrefix("{$filterPrefix}fieldName")
            );
        }
    }

    public function testGetFieldsPrefixes()
    {
        $fieldPrefix = new \Akop\Element\FieldPrefix(["name", "salary"]);

        $filterPrefixes = ["=", "%", "?", ">", "<", "!", "@",
            "!=", "!%", "><", ">=", "<=", "=%", "%=", "!@",
            "!><", "!=%", "!%="
        ];

        foreach ($filterPrefixes as $filterPrefix) {
            $this->assertEquals(
                ["name" => $filterPrefix, "salary" => ">"],
                $fieldPrefix->getFieldsPrefixes(["{$filterPrefix}name", ">salary"])
            );
        }
    }
}
