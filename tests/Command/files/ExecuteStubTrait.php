<?php

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExecuteStubTrait
 */
trait ExecuteStubTrait
{
    /**
     * @var string|null
     */
    protected $executeValue = null;

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_null($this->executeValue)) {
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
