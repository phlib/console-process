<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackgroundCommand
 * @package Phlib\ConsoleProcess\Command
 */
class BackgroundCommand extends Command
{
    /**
     * @var bool
     */
    protected $continue = true;

    /**
     * @var array
     */
    private $signalCallbacks = [];

    /**
     * @var int
     */
    protected $processingDelay = 500000;

    /**
     * @var callable
     */
    private $backgroundExecute;

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        parent::setCode([$this, 'background']);
        $this->backgroundExecute = [$this, 'execute'];

        // add stop signals
        $this->addSignalCallback(SIGTERM, [$this, 'shutdown']);
        $this->addSignalCallback(SIGINT, [$this, 'shutdown']);
    }

    public function setCode(callable $code): self
    {
        $this->backgroundExecute = $code;

        return $this;
    }

    protected function background(InputInterface $input, OutputInterface $output): void
    {
        $this->registerSignals($output);
        if ($output->isVerbose()) {
            $output->writeln('Background PCNTL Signals registered.');
        }

        while ($this->continue) {
            try {
                call_user_func($this->backgroundExecute, $input, $output);
                pcntl_signal_dispatch();
                $this->sleep();
            } catch (\Exception $e) {
                $this->shutdown();
                $this->onException($e, $input, $output);
                throw $e;
            }
        }

        if ($output->isVerbose()) {
            $output->writeln('Background process shutting down.');
        }
        $this->onShutdown($input, $output);
    }

    /**
     * Allows the process to stop itself at the next iteration.
     * @final This method is provided to stop the execution loop. MUST NOT be overridden.
     */
    protected function shutdown(): void
    {
        $this->continue = false;
    }

    protected function onShutdown(InputInterface $input, OutputInterface $output): void
    {
    }

    protected function onException(\Exception $e, InputInterface $input, OutputInterface $output): void
    {
    }

    /**
     * Causes the process to sleep for the number of microseconds as specifed by processing delay property.
     */
    protected function sleep(): void
    {
        usleep($this->processingDelay);
    }

    protected function addSignalCallback(int $signal, callable $callback): self
    {
        if (!array_key_exists($signal, $this->signalCallbacks)) {
            $this->signalCallbacks[$signal] = [];
        }
        $this->signalCallbacks[$signal][] = $callback;
        return $this;
    }

    /**
     * Register each of the added signals for each of the callbacks.
     */
    private function registerSignals(OutputInterface $output): void
    {
        foreach ($this->signalCallbacks as $signal => $callbacks) {
            foreach ($callbacks as $callback) {
                pcntl_signal($signal, function () use ($signal, $output, $callback) {
                    if ($output->isVerbose()) {
                        $output->writeln("Received signal '${signal}', calling registered callback.");
                    }
                    return $callback();
                });
            }
        }
    }
}
