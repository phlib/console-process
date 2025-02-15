<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Command\Stub;

use Phlib\ConsoleProcess\Command\BackgroundCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\Console-Process
 */
class BackgroundCommandStub extends BackgroundCommand
{
    use ExecuteStubTrait;

    public bool $onStartCalled = false;

    public bool $onShutdownCalled = false;

    public bool $onExceptionCalled = false;

    public \Exception $onExceptionCalledWith;

    protected function onStart(InputInterface $input, OutputInterface $output): void
    {
        $this->onStartCalled = true;
    }

    protected function onShutdown(InputInterface $input, OutputInterface $output): void
    {
        $this->onShutdownCalled = true;
    }

    protected function onException(\Exception $e, InputInterface $input, OutputInterface $output): void
    {
        $this->onExceptionCalled = true;
        $this->onExceptionCalledWith = $e;
    }
}
