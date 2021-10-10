<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Command;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BackgroundCommandTest extends TestCase
{
    use PHPMock;

    protected Application $application;

    protected string $commandName = 'foo:bar';

    protected Stub\BackgroundCommandStub $command;

    protected CommandTester $tester;

    public static function setUpBeforeClass(): void
    {
        static::defineFunctionMock(__NAMESPACE__, 'pcntl_signal_dispatch');
        static::defineFunctionMock(__NAMESPACE__, 'usleep');

        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = new Application();
        $this->application->add(new Stub\BackgroundCommandStub($this->commandName));

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

    public function testDefaultSignalCallbacksAreCreated(): void
    {
        $pcntl_signal = $this->getFunctionMock(__NAMESPACE__, 'pcntl_signal');
        $pcntl_signal->expects(static::exactly(2))
            ->withConsecutive([SIGTERM], [SIGINT]);

        $this->tester->execute([]);
    }

    public function testShutdownOnNextIteration(): void
    {
        // Signal dispatch will be called
        $dispatch = $this->getFunctionMock(__NAMESPACE__, 'pcntl_signal_dispatch');
        $dispatch->expects(static::once())
            ->willReturn(true);

        // Sleep between executions
        $sleep = $this->getFunctionMock(__NAMESPACE__, 'usleep');
        $sleep->expects(static::once())
            ->willReturn(true);

        $this->tester->execute([]);

        static::assertSame(1, $this->command->getExecuteCount());
    }

    public function testExitCodeShutdown(): void
    {
        $exitCode = rand(1, 50);

        $this->command->setExitCode($exitCode);

        // Exit before processing signals
        $dispatch = $this->getFunctionMock(__NAMESPACE__, 'pcntl_signal_dispatch');
        $dispatch->expects(static::never());

        // Exit before calling sleep
        $sleep = $this->getFunctionMock(__NAMESPACE__, 'sleep');
        $sleep->expects(static::never());

        $actual = $this->tester->execute([]);

        static::assertSame($exitCode, $actual);
        static::assertSame(1, $this->command->getExecuteCount());
    }
}
