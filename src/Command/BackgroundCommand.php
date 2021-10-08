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
    private bool $continue = true;

    private array $signalCallbacks = [];

    protected int $processingDelay = 500000; // μs,

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

    /**
     * @internal This method is not part of the backward-compatibility promise.
     */
    protected function background(InputInterface $input, OutputInterface $output): int
    {
        $this->registerSignals($output);
        if ($output->isVerbose()) {
            $output->writeln('Background PCNTL Signals registered.');
        }

        $exitCode = 0;
        while ($this->continue) {
            try {
                $exitCode = call_user_func($this->backgroundExecute, $input, $output);
                if ($exitCode > 0) {
                    $this->shutdown();
                    break;
                }
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

        return $exitCode;
    }

    /**
     * For processes with a finite task, this method can be used to stop itself before the next iteration.
     * See README.
     */
    final protected function shutdown(): void
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
    private function sleep(): void
    {
        usleep($this->processingDelay);
    }

    private function addSignalCallback(int $signal, callable $callback): void
    {
        if (!array_key_exists($signal, $this->signalCallbacks)) {
            $this->signalCallbacks[$signal] = [];
        }
        $this->signalCallbacks[$signal][] = $callback;
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
