<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Command;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DaemonCommandTest extends TestCase
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
     * @var Stub\DaemonCommandStub|MockObject
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = new Application();
        $this->application->add(new Stub\DaemonCommandStub($this->commandName));

        $this->command = $this->application->find($this->commandName);
        $this->tester = new CommandTester($this->command);
        $this->command->setOutputCallback(function () {
            return $this->tester->getOutput();
        });
    }

    protected function tearDown(): void
    {
        $this->tester = null;
        $this->command = null;
        $this->application = null;
        parent::tearDown();
    }

    public function testInstanceOfConsoleCommand(): void
    {
        static::assertInstanceOf(Command::class, $this->command);
    }

    public function testBaseCommandClassIsCalled(): void
    {
        static::assertSame($this->commandName, $this->command->getName());
    }

    public function testInstanceOfBackgroundCommand(): void
    {
        static::assertInstanceOf(BackgroundCommand::class, $this->command);
    }

    public function testPidFileOptionIsAdded(): void
    {
        static::assertTrue($this->command->getDefinition()->hasOption('pid-file'));
    }

    public function testActionArgumentIsAdded(): void
    {
        static::assertTrue($this->command->getDefinition()->hasArgument('action'));
    }

    public function testDaemonOptionIsAdded(): void
    {
        static::assertTrue($this->command->getDefinition()->hasOption('daemonize'));
    }

    public function testForkFails(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->setupStartFunctions(-1);
        $pcntl_signal = $this->getFunctionMock(__NAMESPACE__, 'pcntl_signal');
        $pcntl_signal->expects(static::any())
            ->willReturn(true);

        $this->tester->execute([
            'action' => 'start',
            '-p' => '/path/to/my.pid',
            '-d' => true,
        ]);
    }

    public function testChildFailsToGetSession(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->setupStartFunctions(null, -1);
        $pcntl_signal = $this->getFunctionMock(__NAMESPACE__, 'pcntl_signal');
        $pcntl_signal->expects(static::any())
            ->willReturn(true);

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
        $pcntl_signal = $this->getFunctionMock(__NAMESPACE__, 'pcntl_signal');
        $pcntl_signal->expects(static::any())
            ->willReturn(true);

        $this->tester->execute([
            'action' => 'start',
            '-p' => '/path/to/my.pid',
            '-d' => true,
        ]);
        static::assertStringContainsString("${expected}\n", $this->tester->getDisplay());
    }

    public function testChildCallsOnShutdown(): void
    {
        $expected = 'onShutdown called';
        $this->command->setShutdownOutput($expected);
        $this->setupStartFunctions(null);
        $pcntl_signal = $this->getFunctionMock(__NAMESPACE__, 'pcntl_signal');
        $pcntl_signal->expects(static::any())
            ->willReturn(true);

        $this->tester->execute([
            'action' => 'start',
            '-p' => '/path/to/my.pid',
            '-d' => true,
        ]);
        static::assertStringContainsString("${expected}\n", $this->tester->getDisplay());
    }

    public function testStoppingSuccessfully(): void
    {
        $expected = 231;
        $this->setupStopFunctions($expected);

        $posix_kill = $this->getFunctionMock(__NAMESPACE__, 'posix_kill');
        $posix_kill->expects(static::atLeast(2))
            ->with($expected)
            ->willReturn(false);

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
        $pcntl_fork = $this->getFunctionMock(__NAMESPACE__, 'pcntl_fork');
        $pcntl_fork->expects(static::any())->willReturn($fork);

        $posix_setsid = $this->getFunctionMock(__NAMESPACE__, 'posix_setsid');
        $posix_setsid->expects(static::any())->willReturn($setsid);

        $file_exists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $file_exists->expects(static::any())->willReturn($fexists);

        $is_writable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $is_writable->expects(static::any())->willReturn($writeable);

        $is_writable = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $is_writable->expects(static::any())->willReturn($putContents);

        $is_writable = $this->getFunctionMock(__NAMESPACE__, 'unlink');
        $is_writable->expects(static::any())->willReturn(true);

        $is_writable = $this->getFunctionMock(__NAMESPACE__, 'sleep');
        $is_writable->expects(static::any())->willReturn(true);
    }

    protected function setupStopFunctions(
        int $pid,
        bool $fexists = true,
        bool $opened = true,
        bool $withPosixKill = false
    ): void {
        $file_exists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $file_exists->expects(static::any())->willReturn($fexists);

        $fopen = $this->getFunctionMock(__NAMESPACE__, 'fopen');
        $fopen->expects(static::any())->willReturn($opened);

        $fgets = $this->getFunctionMock(__NAMESPACE__, 'fgets');
        $fgets->expects(static::any())->willReturn($pid);

        $fclose = $this->getFunctionMock(__NAMESPACE__, 'fclose');
        $fclose->expects(static::any())->willReturn(true);

        if ($withPosixKill) {
            $posix_kill = $this->getFunctionMock(__NAMESPACE__, 'posix_kill');
            $posix_kill->expects(static::any())->willReturn(false);
        }

        $usleep = $this->getFunctionMock(__NAMESPACE__, 'usleep');
        $usleep->expects(static::any())->willReturn(true);
    }
}
