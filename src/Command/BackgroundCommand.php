<?php

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

    /**
     * @inheritdoc
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        parent::setCode([$this, 'background']);
        $this->backgroundExecute = [$this, 'execute'];

        // add stop signals
        $this->addSignalCallback(SIGTERM, [$this, 'shutdown']);
        $this->addSignalCallback(SIGINT, [$this, 'shutdown']);
    }

    /**
     * @inheritdoc
     */
    public function setCode(callable $code)
    {
        $this->backgroundExecute = $code;

        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    protected function background(InputInterface $input, OutputInterface $output)
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
     * Tell the process to stop running at the next iteration.
     */
    protected function shutdown()
    {
        $this->continue = false;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function onShutdown(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * @param \Exception $e
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function onException(\Exception $e, InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * Causes the process to sleep for the number of microseconds as specifed by processing delay property.
     */
    protected function sleep()
    {
        usleep($this->processingDelay);
    }

    /**
     * @param int $signal
     * @param callable $callback
     * @return $this
     */
    protected function addSignalCallback($signal, callable $callback)
    {
        if (!array_key_exists($signal, $this->signalCallbacks)) {
            $this->signalCallbacks[$signal] = [];
        }
        $this->signalCallbacks[$signal][] = $callback;
        return $this;
    }

    /**
     * Register each of the added signals for each of the callbacks.
     * @param OutputInterface $output
     */
    private function registerSignals(OutputInterface $output)
    {
        foreach ($this->signalCallbacks as $signal => $callbacks) {
            foreach ($callbacks as $callback) {
                pcntl_signal($signal, function() use ($signal, $output, $callback) {
                    if ($output->isVerbose()) {
                        $output->writeln("Received signal '$signal', calling registered callback.");
                    }
                    return $callback();
                });
            }
        }
    }
}
