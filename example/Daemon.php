<?php

use Phlib\ConsoleProcess\Command\DaemonCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class Daemon extends DaemonCommand
{
    protected function configure()
    {
        $this->setName('daemon')
            ->setDescription('Testing Daemon shiz dizzle.');
    }

    protected function onBeforeDaemonize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('onBeforeDaemonize method called.');
    }

    protected function onAfterDaemonizeChild(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('onAfterDaemonizeChild method called.');
    }

    protected function onAfterDaemonizeParent(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('onAfterDaemonizeParent method called.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // do some work
        sleep(1);
    }

    protected function onShutdown(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('onShutdown method called.');
    }

    /**
     * @return ConsoleOutputInterface
     */
    protected function createChildOutput()
    {
        return new StreamOutput(fopen(getcwd() . '/daemon.log', 'a'));
    }
}
