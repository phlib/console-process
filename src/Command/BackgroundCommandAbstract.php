<?php

namespace Phlib\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackgroundCommandAbstract
 * @package Phlib\Console\Command
 */
abstract class BackgroundCommandAbstract extends Command
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
    protected $processingDelay = 5000;

    /**
     * @var callable
     */
    private $backgroundExecute;

    /**
     * @inheritdoc
     */
    public function __construct($name)
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
    public function setCode($code)
    {
        if (!is_callable($code)) {
            throw new \InvalidArgumentException('Invalid callable provided to Command::setCode.');
        }

        $this->backgroundExecute = $code;

        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function background(InputInterface $input, OutputInterface $output)
    {
        $this->registerSignals();
        while ($this->continue) {
            call_user_func($this->backgroundExecute, $input, $output);
            $this->sleep();
            pcntl_signal_dispatch();
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
     * Causes the process to sleep to stop hammering any resources.
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
        if (array_key_exists($signal, $this->signalCallbacks)) {
            $this->signalCallbacks[$signal] = [];
        }
        $this->signalCallbacks[$signal][] = $callback;
        return $this;
    }

    /**
     *
     */
    private function registerSignals()
    {
        foreach ($this->signalCallbacks as $signal => $callbacks) {
            foreach ($callbacks as $callback) {
                pcntl_signal($signal, $callback);
            }
        }
    }
}
