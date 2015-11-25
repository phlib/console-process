<?php

namespace Phlib\ConsoleProcess\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;
use phpmock\phpunit\PHPMock;

class BackgroundCommandTest extends \PHPUnit_Framework_TestCase
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
        require_once __DIR__ . '/files/BackgroundCommandStub.php';
    }

    public function setUp()
    {
        parent::setUp();

        $this->application = new Application();
        $this->application->add(new \BackgroundCommandStub($this->commandName));

        $this->command = $this->application->find($this->commandName);
        $this->tester  = new CommandTester($this->command);
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

    public function testDefaultSignalCallbacksAreCreated()
    {
        $pcntl_signal = $this->getFunctionMock('\Phlib\ConsoleProcess\Command', 'pcntl_signal');
        $pcntl_signal->expects($this->exactly(2))
            ->withConsecutive($this->onConsecutiveCalls(SIGTERM, SIGINT));

        $this->tester->execute([]);
    }
}
