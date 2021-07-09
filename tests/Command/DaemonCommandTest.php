<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Tests\Command;

use phpmock\phpunit\PHPMock;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

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

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/files/DaemonCommandStub.php';
    }

    public function setUp(): void
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

    public function tearDown(): void
    {
        $this->tester = null;
        $this->command = null;
        $this->application = null;
        parent::tearDown();
    }

    public function testInstanceOfConsoleCommand(): void
    {
        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $this->command);
    }

    public function testBaseCommandClassIsCalled(): void
    {
        $this->assertSame($this->commandName, $this->command->getName());
    }

    public function testInstanceOfBackgroundCommand(): void
    {
        $this->assertInstanceOf('\Phlib\ConsoleProcess\Command\BackgroundCommand', $this->command);
    }

    public function testPidFileOptionIsAdded(): void
    {
        $this->assertTrue($this->command->getDefinition()->hasOption('pid-file'));
    }

    public function testActionArgumentIsAdded(): void
    {
        $this->assertTrue($this->command->getDefinition()->hasArgument('action'));
    }

    public function testDaemonOptionIsAdded(): void
    {
        $this->assertTrue($this->command->getDefinition()->hasOption('daemonize'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testForkFails(): void
    {
        $this->setupStartFunctions(-1);
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->any())
            ->will($this->returnValue(true));

        $this->tester->execute([
            'action' => 'start',
            '-p' => '/path/to/my.pid',
            '-d' => true,
        ]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testChildFailsToGetSession(): void
    {
        $this->setupStartFunctions(null, -1);
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->any())
            ->will($this->returnValue(true));

        $this->tester->execute([
            'action' => 'start',
            '-p' => '/path/to/my.pid',
            '-d' => true,
        ]);
    }

    public function testChildExecutesSuccessfully(): void
    {
        $expected = 'execute called';
        $this->command->setExecuteOutput($expected);
        $this->setupStartFunctions(null);
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->any())
            ->will($this->returnValue(true));

        $this->tester->execute([
            'action' => 'start',
            '-p' => '/path/to/my.pid',
            '-d' => true,
        ]);
        $this->assertContains("${expected}\n", $this->tester->getDisplay());
    }

    public function testChildCallsOnShutdown(): void
    {
        $expected = 'onShutdown called';
        $this->command->setShutdownOutput($expected);
        $this->setupStartFunctions(null);
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->any())
            ->will($this->returnValue(true));

        $this->tester->execute([
            'action' => 'start',
            '-p' => '/path/to/my.pid',
            '-d' => true,
        ]);
        $this->assertContains("${expected}\n", $this->tester->getDisplay());
    }

    public function testStoppingSuccessfully(): void
    {
        $expected = 231;
        $this->setupStopFunctions($expected);

        $posix_kill = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'posix_kill');
        $posix_kill->expects($this->atLeast(2))->with($this->equalTo($expected))->willReturn(false);

        $this->tester->execute([
            'action' => 'stop',
            '-p' => '/path/to/my.pid',
        ]);
    }

    protected function setupStartFunctions(
        ?int $fork,
        int $setsid = 0,
        bool $fexists = false,
        bool $writeable = true,
        bool $putContents = true
    ): void {
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

    protected function setupStopFunctions(
        int $pid,
        bool $fexists = true,
        bool $opened = true,
        bool $withPosixKill = false
    ): void {
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
