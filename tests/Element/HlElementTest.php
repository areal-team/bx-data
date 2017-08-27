<?php
namespace AkopTests;

class HlElementTest extends \PHPUnit_Framework_TestCase
{
    // protected $testingClass;

    public function setUp()
    {
        // $this->testingClass = new \Akop\Element\HlElement;
    }

    public function testGetRefFields()
    {
        $elem = $this->getMockBuilder('\Akop\Element\HlElement')
            ->disableOriginalConstructor()
            ->getMock();
        // $elem = new \Akop\Element\HlElement;
        $method = new \ReflectionMethod('\Akop\Element\HlElement', 'getRefFields');
        $method->setAccessible(true);
        $this->assertEquals(
            [['alias' => 'Name', 'fieldname' => 'UF_NAME']],
            $method->invoke($elem, 1)
        );
    }

    public function testGetMapFields()
    {
        $hlblocks = ['1' => 'Brand'];
        $hlblockField = [
            'FIELD_NAME' => 'UF_BRAND',
            'USER_TYPE_ID' => 'hlblock',
            'SETTINGS' => ['HLBLOCK_ID' => 1]
        ];

        $fieldsPattern = [
            'id' => 'ID',
            'brandName' => [
                'name' => 'UF_NAME',
                'data_type' => '\Brand',
                'reference' => [
                      '=this.UF_BRAND' => 'ref.ID'
                ]
            ],
            'brandId' => 'UF_BRAND'
        ];
        $elem = $this->getMockBuilder('\Akop\Element\HlElement')
            ->disableOriginalConstructor()
            ->getMock();

        $prop = new \ReflectionProperty('\Akop\Element\HlElement', 'hlblocks');
        $prop->setAccessible(true);
        $prop->setValue($elem, $hlblocks);

        $method = new \ReflectionMethod('\Akop\Element\HlElement', 'getMapFields');
        $method->setAccessible(true);

        $this->assertEquals(
            $fieldsPattern,
            $method->invoke($elem, [$hlblockField])
        );
    }

}
