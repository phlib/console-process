# phlib/console-process

[![Code Checks](https://img.shields.io/github/workflow/status/phlib/console-process/CodeChecks?logo=github)](https://github.com/phlib/console-process/actions/workflows/code-checks.yml)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/console-process.svg?logo=codecov)](https://codecov.io/gh/phlib/console-process)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/console-process.svg?logo=packagist)](https://packagist.org/packages/phlib/console-process)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/console-process.svg?logo=packagist)](https://packagist.org/packages/phlib/console-process)
![Licence](https://img.shields.io/github/license/phlib/console-process.svg)

Console signal implementation using [PHPs Process control functions](http://php.net/manual/en/book.pcntl.php).

There are 2 implementations.

1. Background command. Repeatedly execute a command, until interrupted using the signal handler.
2. Daemon command. Builds on the Background command to allow forking (detaching) the process.

## Install

Via Composer

``` bash
$ composer require phlib/console-process
```

## Background Command
### Basic Usage

The Background Command is implemented in the same way as you're used to with the
normal Symfony Command, however it must allow for the `execute()` method to be
called multiple times.
There is a processing delay between each execution which can be customised.

```php
<?php

declare(strict_types=1);

use Phlib\Console\Command\BackgroundCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyProcessCommand extends BackgroundCommand
{
    protected function configure(): void
    {
        $this->setName('my:process')
            ->setDescription('My background process.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Doing important work!');
        return 0;
    }
}

```

### Stopping execution

#### Normal usage

Typically, the command will continue execution until interrupted by a signal,
e.g. a user pressing `Ctrl+C`.

#### Self-termination

Alternatively, if an implementation has a finite task, for example deleting
records in batches, it may need to terminate itself once the task is complete.
This is done by calling `shutdown()`.

```php
class MyProcessCommand extends BackgroundCommand
{
    // ...
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $countDeleted = $this->deleteBatchOfRecords();
        if ($countDeleted === 0) {
            $output->writeln('All done!');
            $this->shutdown();
        }
        return 0;
    }
}
```

#### Non-zero exit

If an execution returns a non-zero exit code, iteration will be stopped and the
exit code will be passed back to the console, as with a normal Symfony Command.

```php
class MyProcessCommand extends BackgroundCommand
{
    // ...
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isInputValid = $this->someValidationChecks($input);
        if ($isInputValid === false) {
            $output->writeln('Message explaining invalid input');
            return 1;
        }
        
        // do some work
        
        return 0;
    }
}
```

### Lifecycle Methods

The background command has additional methods that get called when the process
starts and finishes. Useful for any initialising or final cleanup.

  * `onStart(InputInterface $input, OutputInterface $output): void`
    * Similar to standard `initialize()`; useful for `DaemonCommand`.
  * `onShutdown(InputInterface $input, OutputInterface $output): void`
  * `onException(\Exception $e, InputInterface $input, OutputInterface $output): void`

```php
class MyProcessCommand extends BackgroundCommand
{
    // ...
    
    protected function onShutdown(InputInterface $input, OutputInterface $output): void
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

declare(strict_types=1);

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

Once a daemon process is detached, the original output is also lost.
The `--child-log | -o` option can be used to specify a filename to write output.
Alternatively, the `createChildOutput()` method can be overridden to return a
new output instance. For example:

```php
use Symfony\Component\Console\Output\StreamOutput;

class MyProcessCommand extends DaemonCommand
{
    // ... 
    
    protected function createChildOutput()
    {
        return new MyOutputToLoggerClass();
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
    
    protected function onStart(InputInterface $input, OutputInterface $output): void
    {
        // Similar to `onAfterDaemonizeChild()` but also called if `--daemonize` option is not set.
        $output->writeln('onStart method called.');
    }
}
```

### Command Line

```bash
# path/to/my/process start -d
```

```bash
# path/to/my/process status
```

```bash
# path/to/my/process stop
```

####  Options

|Name|Short|Type|Required|Default|Description|
|----|----|-----|--------|-------|-----------|
|action| |Argument|yes| |start, stop, status|
|daemonize|d|Option|no|no|Detaches the process|
|pid-file|p|Option|no|auto|Name of the PID file to use. Not used if daemonize is not set.|
|child-log|o|Option|no|no|Name of the file to save child output. Not used if daemonize is not set.|

## License

This package is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
