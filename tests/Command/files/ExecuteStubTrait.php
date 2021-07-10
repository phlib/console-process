<?php

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ExecuteStubTrait
{
    /**
     * @var string|null
     */
    protected $executeValue = null;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->executeValue !== null) {
            $output->writeln($this->executeValue);
        }
        $this->shutdown();
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setExecuteOutput($value)
    {
        $this->executeValue = $value;
        return $this;
    }
}
