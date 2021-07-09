<?php

declare(strict_types=1);

require_once __DIR__ . '/ExecuteStubTrait.php';

use Phlib\ConsoleProcess\Command\DaemonCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DaemonCommandStub extends DaemonCommand
{
    use ExecuteStubTrait;

    /**
     * @var string|null
     */
    protected $shutdownValue = null;

    /**
     * @var \Closure
     */
    protected $outputCallback;

    protected function onShutdown(InputInterface $input, OutputInterface $output): void
    {
        if ($this->shutdownValue !== null) {
            $output->writeln($this->shutdownValue);
        }
    }

    public function setShutdownOutput(string $value): self
    {
        $this->shutdownValue = $value;
        return $this;
    }

    protected function createChildOutput(): OutputInterface
    {
        return call_user_func($this->outputCallback);
    }

    public function setOutputCallback(\Closure $callback): self
    {
        $this->outputCallback = $callback;
        return $this;
    }
}
