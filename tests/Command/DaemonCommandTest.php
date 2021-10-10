<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Command;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DaemonCommandTest extends TestCase
{
    use PHPMock;

    protected Application $application;

    protected string $commandName = 'foo:bar';

    protected Stub\DaemonCommandStub $command;

    protected CommandTester $tester;

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
        static::assertStringContainsString("{$expected}\n", $this->tester->getDisplay());
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
        static::assertStringContainsString("{$expected}\n", $this->tester->getDisplay());
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

    private function setupStartFunctions(?int $fork, int $setsid = 0): void
    {
        $pcntl_fork = $this->getFunctionMock(__NAMESPACE__, 'pcntl_fork');
        $pcntl_fork->expects(static::any())->willReturn($fork);

        $posix_setsid = $this->getFunctionMock(__NAMESPACE__, 'posix_setsid');
        $posix_setsid->expects(static::any())->willReturn($setsid);

        $file_exists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $file_exists->expects(static::any())->willReturn(false);

        $is_writable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $is_writable->expects(static::any())->willReturn(true);

        $is_writable = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $is_writable->expects(static::any())->willReturn(true);

        $is_writable = $this->getFunctionMock(__NAMESPACE__, 'unlink');
        $is_writable->expects(static::any())->willReturn(true);

        $usleep = $this->getFunctionMock(__NAMESPACE__, 'usleep');
        $usleep->expects(static::any())->willReturn(true);
    }

    private function setupStopFunctions(int $pid): void
    {
        $file_exists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $file_exists->expects(static::any())->willReturn(true);

        $fopen = $this->getFunctionMock(__NAMESPACE__, 'fopen');
        $fopen->expects(static::any())->willReturn(true);

        $fgets = $this->getFunctionMock(__NAMESPACE__, 'fgets');
        $fgets->expects(static::any())->willReturn($pid);

        $fclose = $this->getFunctionMock(__NAMESPACE__, 'fclose');
        $fclose->expects(static::any())->willReturn(true);

        $usleep = $this->getFunctionMock(__NAMESPACE__, 'usleep');
        $usleep->expects(static::any())->willReturn(true);
    }
}
