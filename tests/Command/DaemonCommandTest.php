<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Command;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @package phlib/console-process
 */
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

        $this->command->setOutputCallback(function () {
            return $this->tester->getOutput();
        });

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

        $this->command->setOutputCallback(function () {
            return $this->tester->getOutput();
        });

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

    public function testChildWriteToLogPath(): void
    {
        $expected = 'execute called';
        $logPath = $this->getTestTempFilename(__FUNCTION__, 'log');

        $this->command->setExecuteOutput($expected);

        $this->setupStartFunctions(null);
        $pcntl_signal = $this->getFunctionMock(__NAMESPACE__, 'pcntl_signal');
        $pcntl_signal->expects(static::any())
            ->willReturn(true);

        $this->tester->execute([
            'action' => 'start',
            '-p' => $this->getTestTempFilename(__FUNCTION__, 'pid'),
            '-d' => true,
            '-o' => $logPath,
        ]);

        $actual = file_get_contents($logPath);
        static::assertStringContainsString("{$expected}\n", $actual);

        unlink($logPath);
    }

    public function testChildLogPathErrorDir(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a file');

        $logPath = __DIR__ . '/';

        $this->setupStartFunctions(null);

        $this->tester->execute([
            'action' => 'start',
            '-p' => $this->getTestTempFilename(__FUNCTION__, 'pid'),
            '-d' => true,
            '-o' => $logPath,
        ]);
    }

    public function testChildLogPathErrorFileNotWritable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not writable');

        // Create a temp file and set not writable
        $logPath = $this->getTestTempFilename(__FUNCTION__, 'log');
        touch($logPath);
        chmod($logPath, 0400);

        $this->setupStartFunctions(null);

        try {
            $this->tester->execute([
                'action' => 'start',
                '-p' => $this->getTestTempFilename(__FUNCTION__, 'pid'),
                '-d' => true,
                '-o' => $logPath,
            ]);
        } finally {
            unlink($logPath);
        }
    }

    public function testChildLogPathErrorDirNotWritable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create child log file');

        // Create a temp dir and set not writable
        $logDir = $this->getTestTempFilename(__FUNCTION__, '');
        mkdir($logDir);
        chmod($logDir, 0500);
        $logPath = $logDir . '/cannot-write-this.log';

        $this->setupStartFunctions(null);

        try {
            $this->tester->execute([
                'action' => 'start',
                '-p' => $this->getTestTempFilename(__FUNCTION__, 'pid'),
                '-d' => true,
                '-o' => $logPath,
            ]);
        } finally {
            rmdir($logDir);
        }
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
        return __DIR__ . "/{$functionName}-" . uniqid() .
            ($fileExtension ? '.' . $fileExtension : '');
    }
}
