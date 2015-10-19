<?php

namespace Phlib\Console\Tests\Command;

use Phlib\Console\Command\DaemonCommand;
use phpmock\phpunit\PHPMock;

class DaemonCommandTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    public function testInstanceOfConsoleCommand()
    {
        $command = $this->getMockBuilder('\Phlib\Console\Command\DaemonCommand')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
    }

    public function testBaseCommandClassIsCalled()
    {
        $name = 'foo:bar';
        $command = $this->getMockBuilder('\Phlib\Console\Command\DaemonCommand')
            ->setConstructorArgs([$name])
            ->setMethods($this->getUnmockedMethods([]))
            ->getMockForAbstractClass();
        $this->assertEquals($name, $command->getName());
    }

    public function testDefaultArgumentsAreAdded()
    {
    }

    public function testDefaultSignalCallbacksAreCreated()
    {
    }

    public function getUnmockedMethods(array $mockingMethods)
    {
        $availableMethods = get_class_methods('\Phlib\Console\Command\DaemonCommand');
        var_dump(array_diff($availableMethods, $mockingMethods));
        return array_diff($availableMethods, $mockingMethods);
    }
}
