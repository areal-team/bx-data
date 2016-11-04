<?php

class DependencyResolverTest extends PHPUnit_Framework_TestCase
{
    protected $testingClass;

    public function setUp()
    {
        $this->testingClass = new \Akop\Meta\DependencyResolver;
    }

    public function testAddNode()
    {
        $this->testingClass->addNode('a');
        $this->testingClass->addNode('b');
        $this->testingClass->addNode('c');
        $this->assertEquals( $this->testingClass->getNodes(), ['a', 'b', 'c']);

    }

    public function testAddNodeArray()
    {

        $this->testingClass->addNodes(['a', 'b', 'c']);
        $this->assertEquals( $this->testingClass->getNodes(), ['a', 'b', 'c']);

    }

    public function testAddEdge()
    {
        $this->addNodesAndEdgesSimple();
        $this->assertEquals( $this->testingClass->getEdges(), ['a' => ['b'], 'b' =>['c']]);

    }


    public function testResolveSimple()
    {
        $this->addNodesAndEdgesSimple();
        $this->assertEquals($this->testingClass->resolve('a'), ['c', 'b', 'a']);
    }

    public function testResolve()
    {
        $this->addNodesAndEdges();
        $this->assertEquals($this->testingClass->resolve('a'), ['d', 'e', 'c', 'b', 'a']);
    }

    /**
     * @expectedException     Exception
     * @expectedExceptionCode 424
     */
    public function testCircularDependencyThrowException()
    {
        $this->addNodesAndEdges();
        $this->testingClass->addEdge('d', 'b');
        $this->testingClass->resolve('a');
    }

    public function tearDown()
    {
        unset($this->testingClass);
    }

    private function addNodesAndEdgesSimple()
    {
        $this->testingClass->addNodes(['a', 'b', 'c']);
        $this->testingClass->addEdge('a', 'b');
        $this->testingClass->addEdge('b', 'c');
    }

    private function addNodesAndEdges()
    {
        $this->testingClass->addNodes(['a', 'b', 'c', 'd', 'e']);
        $this->testingClass->addEdge('a', 'b');
        $this->testingClass->addEdge('a', 'd');
        $this->testingClass->addEdge('b', 'c');
        $this->testingClass->addEdge('b', 'e');
        $this->testingClass->addEdge('c', 'd');
        $this->testingClass->addEdge('c', 'e');
    }

    private function clearNodesAndEdges()
    {
    }
}


?>