<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Tests\Command;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class BackgroundCommandTest extends TestCase
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
     * @var \BackgroundCommandStub|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $tester;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/files/BackgroundCommandStub.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = new Application();
        $this->application->add(new \BackgroundCommandStub($this->commandName));

        $this->command = $this->application->find($this->commandName);
        $this->tester = new CommandTester($this->command);
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
        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $this->command);
    }

    public function testBaseCommandClassIsCalled(): void
    {
        $this->assertSame($this->commandName, $this->command->getName());
    }

    public function testDefaultSignalCallbacksAreCreated(): void
    {
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->exactly(2))
            ->withConsecutive([SIGTERM], [SIGINT]);

        $this->tester->execute([]);
    }
}
