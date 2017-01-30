# phlib/console-process

[![Build Status](https://img.shields.io/travis/phlib/console-process/master.svg)](https://travis-ci.org/phlib/console-process)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/console-process.svg)](https://codecov.io/gh/phlib/console-process)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/console-process.svg)](https://packagist.org/packages/phlib/console-process)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/console-process.svg)](https://packagist.org/packages/phlib/console-process)

Console signal implementation using [PHPs Process control functions](http://php.net/manual/en/book.pcntl.php).

There are 2 implementations.

1. Background command. Allows a process to be interrupted using the signal handler.
2. Daemon command. Builds on the Background command to allow forking (detaching) the process.

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

### Lifecycle Methods

The background command has one additional method, to the standard Symfony command methods, which gets called 
when the process finishes. The method is ```onShutdown``` which takes a ```InputInterface``` and 
an ```OutputInterface```. This can be used for any final cleanup for example.

```php
class MyProcessCommand extends BackgroundCommand
{
    // ...
    
    protected function onShutdown(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('onShutdown method called.');
    }
}
```

## Daemon Command
### Basic Usage

Apart from extending a different class, the Daemon Command looks and works in a similar way to the Background
Command. The PID file argument is optional. If it is not specified the process generates it's own PID file based 
on the command name.

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

### Output

Once a daemon process is detached, the original output is also lost. The daemon command class provides a 
protected method to specify a new output. If this method isn't overriden, then by default, the output is a
```NullOutput``` Symfony object. The following example demonstrates overriding the method.

```php
use Symfony\Component\Console\Output\StreamOutput;

class MyProcessCommand extends DaemonCommand
{
    // ... 
    
    protected function createChildOutput()
    {
        return new StreamOutput(fopen(getcwd() . '/my-daemon.log', 'a'));
    }
}
```

### Lifecycle Methods

The daemon command has additional methods, to the standard Symfony command methods and the background command, 
which gets called during the process lifecycle. The following example demonstrates overriding the methods.

```php
class MyProcessCommand extends DaemonCommand
{
    // ...

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
}
```

## Command Line

```bash
# path/to/my/process start -d
```

```bash
# path/to/my/process status
```

```bash
# path/to/my/process stop
```

###  Options
|Name|Short|Type|Required|Default|Description|
|----|----|-----|--------|-------|-----------|
|action||Argument|yes||start, stop, status|
|daemonize|d|Option|no|no|Detaches the process|
|pid-file|p|Option|no|auto|Name of the PID file to use. Not used if daemonize is not set.|

