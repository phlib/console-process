<?php

namespace Phlib\ConsoleProcess\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DaemonCommand
 * @package Phlib\ConsoleProcess\Console
 */
class DaemonCommand extends BackgroundCommand
{
    /**
     * @inheritdoc
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->configureDaemon();
    }

    protected function configureDaemon()
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'Start, stop or status.')
            ->addOption('pid-file', 'p', InputOption::VALUE_REQUIRED, 'PID file location.', false)
            ->addOption('no-daemonize', 'd', InputOption::VALUE_NONE, 'Do not daemonize the process.');
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
     * {@inheritDoc}
     */
    protected function background(InputInterface $input, OutputInterface $output)
    {
        $action = strtolower($input->getArgument('action'));
        if (!in_array($action, ['start', 'stop', 'status'])) {
            throw new \InvalidArgumentException("Provided action is invalid, expecting 'start', 'stop' or 'status'.");
        }

        return $this->$action($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    private function start(InputInterface $input, OutputInterface $output)
    {
        // pid file check
        $pidFile = $this->getPidFilename($input);
        if (file_exists($pidFile) || !is_writable(dirname($pidFile))) {
            throw new \InvalidArgumentException(sprintf("Can not write to PID file '%s'", $pidFile));
        }

        if (!$input->getOption('no-daemonize')) {
            $this->onBeforeDaemonize($input, $output);
            $isChild = $this->daemonize();
            if (!$isChild) {
                $this->onAfterDaemonizeParent($input, $output);
                return;
            }

            // child shouldn't hold onto parents input/output
            $input = clone $input;
            $output = $this->createChildOutput();
            $this->onAfterDaemonizeChild($input, $output);
        }

        $written = file_put_contents($pidFile, getmypid());
        if (!$written) {
            throw new \RuntimeException(sprintf("Failed to write PID file '%s'", $pidFile));
        }

        try {
            parent::background($input, $output);
        } catch (\Exception $e) {
            unlink($pidFile);
            throw $e;
        }

        unlink($pidFile);
    }

    /**
     * Split into a new process. Returns true when it's the child process and false
     * for the parent process.
     *
     * @return bool
     */
    private function daemonize()
    {
        // prevent permission issues
        umask(0);

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
        $pidFile = $this->getPidFilename($input);

        // pid file check
        $fileHandle = @fopen($pidFile, 'r');
        if (!file_exists($pidFile) || !$fileHandle) {
            throw new \RuntimeException(sprintf("PID file doesn't exist '%s'", $pidFile));
        }

        $pid = (int)fgets($fileHandle);
        fclose($fileHandle);

        if ($pid > 0) {
            do {
                posix_kill($pid, SIGTERM);
                usleep(500000);
            } while(posix_kill($pid, 0));
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function status(InputInterface $input, OutputInterface $output)
    {
        $pidFile = $this->getPidFilename($input);
        if (!file_exists($pidFile)) {
            $output->writeln("Not running");
            return;
        }

        $fileHandle = @fopen($pidFile, 'r');
        if (!$fileHandle) {
            $output->writeln("PID file exists but is not readable.");
        }

        $pid = (int)fgets($fileHandle);
        fclose($fileHandle);

        $running = posix_kill($pid, 0);
        if ($running) {
            $output->writeln("Running (PID: $pid)");
        } else {
            $output->writeln("Not running");
        }
    }

    /**
     * @return ConsoleOutputInterface
     */
    protected function createChildOutput()
    {
        return new NullOutput();
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function getPidFilename(InputInterface $input)
    {
        // pid file check
        $pidFile = $input->getOption('pid-file');
        if ($pidFile === false) {
            // no file specified, generate our own name
            $baseDir = getcwd();
            $name    = str_replace(' ', '-', strtolower($this->getName()));
            $pidFile = $baseDir . DIRECTORY_SEPARATOR . $name . '.pid';
        }

        return $pidFile;
    }
}
