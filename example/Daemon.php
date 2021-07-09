<?php

declare(strict_types=1);

use Phlib\ConsoleProcess\Command\DaemonCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class Daemon extends DaemonCommand
{
    protected function configure(): void
    {
        $this->setName('daemon')
            ->setDescription('Testing Daemon shiz dizzle.');
    }

    protected function onBeforeDaemonize(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('onBeforeDaemonize method called.');
    }

    protected function onAfterDaemonizeChild(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('onAfterDaemonizeChild method called.');
    }

    protected function onAfterDaemonizeParent(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('onAfterDaemonizeParent method called.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // do some work
        sleep(1);
        return 0;
    }

    protected function onShutdown(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('onShutdown method called.');
    }

    protected function createChildOutput(): OutputInterface
    {
        return new StreamOutput(fopen(getcwd() . '/daemon.log', 'a'));
    }
}
