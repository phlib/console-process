<?php

namespace Phlib\ConsoleProcess\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;
use phpmock\phpunit\PHPMock;

class DaemonCommandTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var string
     */
    protected $commandName = 'foo:bar';

    /**
     * @var \TestCommand|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $tester;

    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/files/DaemonCommandStub.php';
    }

    public function setUp()
    {
        parent::setUp();

        $this->application = new Application();
        $this->application->add(new \DaemonCommandStub($this->commandName));

        $this->command = $this->application->find($this->commandName);
        $this->tester = new CommandTester($this->command);
        $this->command->setOutputCallback(function () {
            return $this->tester->getOutput();
        });
    }

    public function tearDown()
    {
        $this->tester = null;
        $this->command = null;
        $this->application = null;
        parent::tearDown();
    }

    public function testInstanceOfConsoleCommand()
    {
        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $this->command);
    }

    public function testBaseCommandClassIsCalled()
    {
        $this->assertEquals($this->commandName, $this->command->getName());
    }

    public function testInstanceOfBackgroundCommand()
    {
        $this->assertInstanceOf('\Phlib\ConsoleProcess\Command\BackgroundCommand', $this->command);
    }

    public function testPidFileArgumentIsAdded()
    {
        $this->assertTrue($this->command->getDefinition()->hasArgument('pid-file'));
    }

    public function testActionArgumentIsAdded()
    {
        $this->assertTrue($this->command->getDefinition()->hasArgument('action'));
    }

    public function testDaemonOptionIsAdded()
    {
        $this->assertTrue($this->command->getDefinition()->hasOption('no-daemonize'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testForkFails()
    {
        $this->setupStartFunctions(-1);
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->any())
            ->will($this->returnValue(true));

        $this->tester->execute([
            'pid-file' => '/path/to/my.pid',
            'action' => 'start',
            '-d' => false
        ]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testChildFailsToGetSession()
    {
        $this->setupStartFunctions(null, -1);
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->any())
            ->will($this->returnValue(true));

        $this->tester->execute([
            'pid-file' => '/path/to/my.pid',
            'action' => 'start',
            '-d' => false
        ]);
    }

    public function testChildExecutesSuccessfully()
    {
        $expected = 'execute called';
        $this->command->setExecuteOutput($expected);
        $this->setupStartFunctions(null);
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->any())
            ->will($this->returnValue(true));

        $this->tester->execute([
            'pid-file' => '/path/to/my.pid',
            'action' => 'start',
            '-d' => false
        ]);
        $this->assertEquals("$expected\n", $this->tester->getDisplay());
    }

    public function testChildCallsOnShutdown()
    {
        $expected = 'onShutdown called';
        $this->command->setShutdownOutput($expected);
        $this->setupStartFunctions(null);
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->any())
            ->will($this->returnValue(true));

        $this->tester->execute([
            'pid-file' => '/path/to/my.pid',
            'action' => 'start',
            '-d' => false
        ]);
        $this->assertEquals("$expected\n", $this->tester->getDisplay());
    }

    public function testStoppingSuccessfully()
    {
        $expected = 231;
        $this->setupStopFunctions($expected);

        $posix_kill = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'posix_kill');
        $posix_kill->expects($this->atLeast(2))->with($this->equalTo($expected))->willReturn(false);

        $this->tester->execute([
            'pid-file' => '/path/to/my.pid',
            'action' => 'stop'
        ]);
    }

//    /**
//     * @expectedException \RuntimeException
//     */
//    public function testStoppingWhenInvalidPidFile()
//    {
//        $expected = 231;
//        $this->setupStopFunctions($expected);
//
//        $posix_kill = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'posix_kill');
//        $posix_kill->expects($this->atLeast(2))->with($this->equalTo($expected))->willReturn(false);
//
//        $this->tester->execute([
//            'pid-file' => '/path/to/my.pid',
//            'action' => 'stop'
//        ]);
//    }

    protected function setupStartFunctions($fork, $setsid = 0, $fexists = false, $writeable = true, $putContents = true)
    {
        $namespace = '\Phlib\ConsoleProcess\Command';

        $pcntl_fork = $this->getFunctionMock($namespace, 'pcntl_fork');
        $pcntl_fork->expects($this->any())->willReturn($fork);

        $posix_setsid = $this->getFunctionMock($namespace, 'posix_setsid');
        $posix_setsid->expects($this->any())->willReturn($setsid);

        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_exists->expects($this->any())->willReturn($fexists);

        $is_writable = $this->getFunctionMock($namespace, 'is_writable');
        $is_writable->expects($this->any())->willReturn($writeable);

        $is_writable = $this->getFunctionMock($namespace, 'file_put_contents');
        $is_writable->expects($this->any())->willReturn($putContents);

        $is_writable = $this->getFunctionMock($namespace, 'unlink');
        $is_writable->expects($this->any())->willReturn(true);

        $is_writable = $this->getFunctionMock($namespace, 'sleep');
        $is_writable->expects($this->any())->willReturn(true);
    }

    protected function setupStopFunctions($pid, $fexists = true, $opened = true, $withPosixKill = false)
    {
        $namespace = '\Phlib\ConsoleProcess\Command';

        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_exists->expects($this->any())->willReturn($fexists);

        $fopen = $this->getFunctionMock($namespace, 'fopen');
        $fopen->expects($this->any())->willReturn($opened);

        $fgets = $this->getFunctionMock($namespace, 'fgets');
        $fgets->expects($this->any())->willReturn($pid);

        $fclose = $this->getFunctionMock($namespace, 'fclose');
        $fclose->expects($this->any())->willReturn(true);

        if ($withPosixKill) {
            $posix_kill = $this->getFunctionMock($namespace, 'posix_kill');
            $posix_kill->expects($this->any())->willReturn(false);
        }

        $usleep = $this->getFunctionMock($namespace, 'usleep');
        $usleep->expects($this->any())->willReturn(true);
    }
}
