<?php

namespace Phlib\Console\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
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
        $pcntl_fork = $this->getFunctionMock('\Phlib\Console\Command', 'pcntl_fork');
        $pcntl_fork->expects($this->any())
            ->willReturn(321);
        $pcntl_setsid = $this->getFunctionMock('\Phlib\Console\Command', 'pcntl_setsid');
        $pcntl_setsid->expects($this->any())
            ->willReturn(0);
        $file_exists = $this->getFunctionMock('\Phlib\Console\Command', 'file_exists');
        $file_exists->expects($this->any())
            ->willReturn(false);
        $is_writable = $this->getFunctionMock('\Phlib\Console\Command', 'is_writable');
        $is_writable->expects($this->any())
            ->willReturn(true);

        $name = 'foo:bar';
        $command = $this->getMockBuilder('\Phlib\Console\Command\DaemonCommand')
            ->setConstructorArgs([$name])
            ->getMockForAbstractClass();
        $this->assertEquals($name, $command->getName());

        $tester = new CommandTester($command);
        $tester->execute([
            'pid-file' => '/var/run/my.pid',
            'action' => 'start',
            '-d' => true
        ]);
    }

//    public function testDefaultArgumentsAreAdded()
//    {
//    }
//
//    public function testDefaultSignalCallbacksAreCreated()
//    {
//    }
//
//    public function getUnmockedMethods(array $mockingMethods)
//    {
//        $availableMethods = get_class_methods('\Phlib\Console\Command\DaemonCommand');
//        var_dump(array_diff($availableMethods, $mockingMethods));
//        return array_diff($availableMethods, $mockingMethods);
//    }
}
