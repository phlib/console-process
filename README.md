# phlib/console-process

[![Build Status](https://img.shields.io/travis/phlib/console-process/master.svg)](https://travis-ci.org/phlib/console-process)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/console-process.svg)](https://packagist.org/packages/phlib/console-process)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/console-process.svg)](https://packagist.org/packages/phlib/console-process)

Console signal implementation using [PHPs Process control functions](http://php.net/manual/en/book.pcntl.php).

There are 2 implementations.

1. Background command. Allows a process to be interrupted using the signal handler.
2. Daemon command. Builds on the Background command to allow forking the process.

## Install

Via Composer

``` bash
$ composer require phlib/console-process
```

## Background Command
### Basic Usage

The Background Command works in the same way as you're used to with the normal Symfony Command.

```php
<?php

use Phlib\Console\Command\BackgroundCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyProcessCommand extends BackgroundCommand
{
    protected function configure()
    {
        $this->setName('my:process')
            ->setDescription('My background process.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Doing important work!');
    }
}

```

## Daemon Command
### Basic Usage

Apart from extending a different class, the Daemon Command looks and works in a similar way to the Background
Command.

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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
