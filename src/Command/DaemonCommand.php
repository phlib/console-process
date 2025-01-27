<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @package Phlib\Console-Process
 */
class DaemonCommand extends BackgroundCommand
{
    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->configureDaemon();
    }

    private function configureDaemon(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'Start, stop or status.')
            ->addOption('pid-file', 'p', InputOption::VALUE_REQUIRED, 'PID file location.', false)
            ->addOption('daemonize', 'd', InputOption::VALUE_NONE, 'Make the process run in the background and detach.')
            ->addOption(
                'child-log',
                'o',
                InputOption::VALUE_REQUIRED,
                'File location to log child output, when using <info>--daemonize</info>',
            );
    }

    protected function onBeforeDaemonize(InputInterface $input, OutputInterface $output): void
    {
    }

    protected function onAfterDaemonizeParent(InputInterface $input, OutputInterface $output): void
    {
    }

    protected function onAfterDaemonizeChild(InputInterface $input, OutputInterface $output): void
    {
    }

    final protected function background(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower($input->getArgument('action'));
        switch ($action) {
            case 'start':
                return $this->start($input, $output);
            case 'stop':
                return $this->stop($input, $output);
            case 'status':
                return $this->status($input, $output);
            default:
                throw new \InvalidArgumentException(
                    "Provided action is invalid, expecting 'start', 'stop' or 'status'."
                );
        }
    }

    private function start(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = null;
        if ($input->getOption('daemonize')) {
            // pid file check
            $pidFile = $this->getPidFilename($input);
            if (file_exists($pidFile)) {
                throw new \InvalidArgumentException(sprintf("PID file '%s' already exists.", $pidFile));
            }
            if (!is_writable(dirname($pidFile))) {
                throw new \InvalidArgumentException(sprintf("Can not write to PID file '%s'.", $pidFile));
            }

            $this->onBeforeDaemonize($input, $output);
            $isChild = $this->daemonize();
            if (!$isChild) {
                $output->writeln('Parent process completing.', OutputInterface::VERBOSITY_VERBOSE);
                $this->onAfterDaemonizeParent($input, $output);
                return 0;
            }

            // children shouldn't hold onto parents input/output
            $childLogFilename = $input->getOption('child-log');
            $input = $this->recreateInput($input);
            $output = $this->recreateOutput($output, $childLogFilename);

            $output->writeln('Child process forked.');
            $this->onAfterDaemonizeChild($input, $output);

            $written = file_put_contents($pidFile, getmypid());
            if (!$written) {
                throw new \RuntimeException(sprintf("Failed to write PID file '%s'", $pidFile));
            }
            $output->writeln("PID file written to '{$pidFile}'.");
        }

        try {
            $output->writeln('Daemon executing main process.');
            return parent::background($input, $output);
        } finally {
            $output->writeln('Daemon process shutting down.');
            if ($pidFile !== null && file_exists($pidFile)) {
                unlink($pidFile);
            }
        }
    }

    /**
     * Split into a new process. Returns true when it's the child process and false
     * for the parent process.
     */
    private function daemonize(): bool
    {
        // prevent permission issues
        umask(0);

        $pid = pcntl_fork();
        if ($pid === -1) {
            /* fork failed */
            throw new \RuntimeException('Failed to fork the daemon.');
        } elseif ($pid) {
            /* close the parent */
            return false;
        }

        // make ourselves the session leader
        if (posix_setsid() === -1) {
            throw new \RuntimeException('Failed to become a daemon.');
        }

        return true;
    }

    private function stop(InputInterface $input, OutputInterface $output): int
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
            } while (posix_kill($pid, 0));
        }

        return 0;
    }

    private function status(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = $this->getPidFilename($input);
        if (!file_exists($pidFile)) {
            $output->writeln('Not running');
            return 0;
        }

        $fileHandle = @fopen($pidFile, 'r');
        if (!$fileHandle) {
            $output->writeln('PID file exists but is not readable.');
        }

        $pid = (int)fgets($fileHandle);
        fclose($fileHandle);

        $running = posix_kill($pid, 0);
        if ($running) {
            $output->writeln("Running (PID: {$pid})");
        } else {
            $output->writeln('Not running');
        }

        return 0;
    }

    private function recreateInput(InputInterface $input): InputInterface
    {
        return clone $input;
    }

    private function recreateOutput(OutputInterface $output, ?string $childLogFilename): OutputInterface
    {
        $verbosityLevel = $output->getVerbosity();
        $newInstance = $this->createChildOutput($childLogFilename);
        $newInstance->setVerbosity($verbosityLevel);
        return $newInstance;
    }

    protected function createChildOutput(?string $childLogFilename): OutputInterface
    {
        if (empty($childLogFilename)) {
            return new NullOutput();
        }

        if (file_exists($childLogFilename)) {
            if (!is_file($childLogFilename)) {
                throw new \InvalidArgumentException(sprintf("Child log file '%s' is not a file.", $childLogFilename));
            }
            if (!is_writable($childLogFilename)) {
                throw new \InvalidArgumentException(sprintf("Child log file '%s' is not writable.", $childLogFilename));
            }
        } elseif (!is_writable(dirname($childLogFilename))) {
            throw new \InvalidArgumentException(sprintf("Cannot create child log file '%s'.", $childLogFilename));
        }

        return new StreamOutput(fopen($childLogFilename, 'a'));
    }

    private function getPidFilename(InputInterface $input): string
    {
        // pid file check
        $pidFile = $input->getOption('pid-file');
        if ($pidFile === false) {
            // no file specified, generate our own name
            $baseDir = getcwd();
            $name = str_replace(' ', '-', strtolower($this->getName()));
            $pidFile = $baseDir . DIRECTORY_SEPARATOR . $name . '.pid';
        }

        return $pidFile;
    }
}
