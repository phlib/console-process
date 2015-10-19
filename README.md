# phlib/console

Console implementation.

## Install

Via Composer

``` bash
$ composer require phlib/console
```
or
``` JSON
"require": {
    "phlib/console": "*"
}
```

## Basic Usage

```php
<?php

use Phlib\Console\Command\DaemonCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyProcessCommand extends DaemonCommand
{
    protected function configure()
    {
        $this->setName('my:process')
            ->setDescription('My background process.');
            
        $this->processingDelay = 3;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Doing important work!');
    }
}

```

```bash
# path/to/my/process /path/to/my.pid start -d
```

```bash
# path/to/my/process /path/to/my.pid stop
```
