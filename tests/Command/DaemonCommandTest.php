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
            '-p' => $this->getTestTempFilename(__FUNCTION__, 'pid'),
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
            '-p' => $this->getTestTempFilename(__FUNCTION__, 'pid'),
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
            '-p' => $this->getTestTempFilename(__FUNCTION__, 'pid'),
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
            '-p' => $this->getTestTempFilename(__FUNCTION__, 'pid'),
            '-d' => true,
        ]);
        static::assertStringContainsString("{$expected}\n", $this->tester->getDisplay());
    }

    public function testStoppingSuccessfully(): void
    {
        $expectedPid = rand(1, 99999);
        $pidFile = $this->getTestTempFilename(__FUNCTION__, 'pid');

        // Set the pidFile to have the expected PID which should be passed to `posix_kill()`
        file_put_contents($pidFile, $expectedPid);

        $posix_kill = $this->getFunctionMock(__NAMESPACE__, 'posix_kill');
        $posix_kill->expects(static::exactly(2))
            ->withConsecutive(
                [$expectedPid, SIGTERM],
                [$expectedPid, 0],
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
            );

        // Remove the delay used to wait for a real process to exit
        $usleep = $this->getFunctionMock(__NAMESPACE__, 'usleep');
        $usleep->expects(static::any())->willReturn(true);

        $this->tester->execute([
            'action' => 'stop',
            '-p' => $pidFile,
        ]);

        unlink($pidFile);
    }

    private function setupStartFunctions(?int $fork, int $setsid = 0): void
    {
        $pcntl_fork = $this->getFunctionMock(__NAMESPACE__, 'pcntl_fork');
        $pcntl_fork->expects(static::any())->willReturn($fork);

        $posix_setsid = $this->getFunctionMock(__NAMESPACE__, 'posix_setsid');
        $posix_setsid->expects(static::any())->willReturn($setsid);

        $usleep = $this->getFunctionMock(__NAMESPACE__, 'usleep');
        $usleep->expects(static::any())->willReturn(true);
    }

    private function getTestTempFilename(string $functionName, string $fileExtension): string
    {
        return __DIR__ . "/{$functionName}-" . uniqid() . '.' . $fileExtension;
    }
}
