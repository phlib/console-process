<?php

namespace Phlib\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DaemonCommand
 * @package Phlib\Console\Console
 */
abstract class DaemonCommand extends Command
{
    /**
     * @var bool
     */
    private $continue = true;

    /**
     * @var array
     */
    private $signalCallbacks = [];

    /**
     * @var int
     */
    protected $processingDelay = 1; //second

    /**
     * @inheritdoc
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        // add default arguments
        $this->addArgument('pid-file', InputArgument::REQUIRED, 'PID file location.')
            ->addArgument('action', InputArgument::REQUIRED, 'Start or stop the process.')
            ->addOption('daemonize', 'd', null, 'Make the process a background process (fork).');

        // add stop signals
        $this->addSignalCallback(SIGTERM, [$this, 'shutdown']);
        $this->addSignalCallback(SIGINT, [$this, 'shutdown']);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function onBeforeDaemonize(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function onAfterDaemonizeParent(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function onAfterDaemonizeChild(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    abstract protected function doExecute(InputInterface $input, OutputInterface $output);

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function onShutdown(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = strtolower($input->getArgument('action'));
        if (!in_array($action, ['start', 'stop'])) {
            throw new \InvalidArgumentException("Provided action is invalid, expecting 'start' or 'stop'.");
        }

        if ($action == 'start') {
            $this->start($input, $output);
        } else {
            $this->stop($input, $output);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function start(InputInterface $input, OutputInterface $output)
    {
        // pid file check
        $pidFile = $input->getArgument('pid-file');
        if (file_exists($pidFile) || !is_writable(dirname($pidFile))) {
            throw new \InvalidArgumentException(sprintf("Can not write to PID file '%s'", $pidFile));
        }

        $this->registerSignals($input, $output);

        if ($input->getOption('daemonize')) {
            $this->onBeforeDaemonize($input, $output);
            $isChild = $this->daemonize();
            if (!$isChild) {
                $this->onAfterDaemonizeParent($input, $output);
                return;
            }

            // child shouldn't hold onto parents input/output
            $input  = clone $input;
            $output = $this->createChildOutput();
            $this->onAfterDaemonizeChild($input, $output);
        }

        $written = file_put_contents($pidFile, getmypid());
        if (!$written) {
            throw new \RuntimeException(sprintf("Failed to write PID file '%s'", $pidFile));
        }

        do {
            $this->doExecute($input, $output);

            $this->sleep();
            pcntl_signal_dispatch();
        } while ($this->continue);


        unlink($pidFile);
        $this->onShutdown($input, $output);
    }

    /**
     * Split into a new process. Returns true when it's the child process and false
     * for the parent process.
     *
     * @return bool
     */
    private function daemonize()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            /* fork failed */
            throw new \RuntimeException('Failed to fork the daemon.');
        } elseif ($pid) {
            /* close the parent */
            return false;
        }

        // make ourselves the session leader
        if (posix_setsid() == -1) {
            throw new \RuntimeException('Failed to become a daemon.');
        }

        return true;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function stop(InputInterface $input, OutputInterface $output)
    {
        $pidFile = $input->getArgument('pid-file');

        // pid file check
        $fh = @fopen($pidFile, 'r');
        if (!file_exists($pidFile) || !$fh) {
            throw new \RuntimeException(sprintf("PID file doesn't exist '%s'", $pidFile));
        }

        $pid = (int)fgets($fh);
        fclose($fh);

        if ($pid > 0) {
            do {
                posix_kill($pid, SIGTERM);
                usleep(500000);
            } while(posix_kill($pid, 0));
        }
    }

    /**
     * Tell the process to stop running at the next iteration.
     */
    protected function shutdown()
    {
        $this->continue = false;
    }

    /**
     * Causes the process to sleep to stop hammering any resources.
     */
    protected function sleep()
    {
        sleep($this->processingDelay);
    }

    /**
     * @param int $signal
     * @param callable $callback
     * @return $this
     */
    protected function addSignalCallback($signal, callable $callback)
    {
        $this->signalCallbacks[$signal][] = $callback;
        return $this;
    }

    private function registerSignals()
    {
        foreach ($this->signalCallbacks as $signal => $callbacks) {
            foreach ($callbacks as $callback) {
                pcntl_signal($signal, $callback);
            }
        }
    }

    /**
     * @return ConsoleOutputInterface
     */
    protected function createChildOutput()
    {
        return new NullOutput();
    }
}
