<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Command\Stub;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ExecuteStubTrait
{
    /**
     * @var string|null
     */
    protected $executeValue = null;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->executeValue !== null) {
            $output->writeln($this->executeValue);
        }
        $this->shutdown();

        return 0;
    }

    public function setExecuteOutput(string $value): self
    {
        $this->executeValue = $value;
        return $this;
    }
}
