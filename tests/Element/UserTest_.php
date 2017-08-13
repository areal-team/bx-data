<?php
namespace AkopTests;

/**
 * @todo протестировать невозможность получения данных неадмином
 * @todo протестировать возможность получения данных админом
 */
class UserTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
    }

    public function _testAdminOnlyException()
    {
        // $method = new \ReflectionMethod('\Akop\Element\User', 'isAdmin');
        // $method->setAccessible(true);

        $stub = $this->getMock('\Akop\Element\User', ['isAdmin']);
        $stub->method('isAdmin')
             ->willReturn(false);
        $user = new \Akop\Element\User;
        // $this->assertEquals($user1, $stub->getRow());
        $this->expectException(Exception::class);

    }

    public function _testAdminOnly()
    {
        // $method = new \ReflectionMethod('\Akop\Element\User', 'isAdmin');
        // $method->setAccessible(true);

        $stub = $this->getMock('\Akop\Element\User', ['isAdmin']);
        $stub->method('isAdmin')
             ->willReturn(true);

        // $this->assertEquals($user1, $stub->getRow());
        $this->expectException(Exception::class);

    }
}
