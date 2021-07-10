<?php

declare(strict_types=1);

use Phlib\ConsoleProcess\Command\BackgroundCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Background extends BackgroundCommand
{
    protected function configure(): void
    {
        $this->setName('background')
            ->setDescription('Testing Background shiz dizzle.');
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
}
