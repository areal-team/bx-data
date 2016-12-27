<?php
namespace AkopTests;

class HlElementTest extends \PHPUnit_Framework_TestCase
{
    // protected $testingClass;

    public function setUp()
    {
        // $this->testingClass = new \Akop\Element\HlElement;
    }

    public function _testFieldsNotAnEmptyArray()
    {
        $stub = $this->getMockBuilder('\Akop\Element\HlElement')
            ->disableOriginalConstructor()
            ->getMock();

        // $stub = new \Akop\Element\HlElement;
        var_dump($stub);
        var_dump($stub->getFields());
        $this->assertEquals(["ID"], $stub->getFields());
    }
}
