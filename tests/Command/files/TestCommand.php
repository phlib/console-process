<?php

use Phlib\Console\Command\DaemonCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends DaemonCommand
{
    protected $executeValue = null;
    protected $shutdownValue = null;
    protected $outputCallback;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_null($this->executeValue)) {
            $output->writeln($this->executeValue);
        }
        $this->shutdown();
    }

    protected function onShutdown(InputInterface $input, OutputInterface $output)
    {
        if (!is_null($this->shutdownValue)) {
            $output->writeln($this->shutdownValue);
        }
    }

    public function setExecuteOutput($value)
    {
        $this->executeValue = $value;
        return $this;
    }

    public function setShutdownOutput($value)
    {
        $this->shutdownValue = $value;
        return $this;
    }

    protected function createChildOutput()
    {
        return call_user_func($this->outputCallback);
    }

    public function setOutputCallback(\Closure $callback)
    {
        $this->outputCallback = $callback;
        return $this;
    }
}
