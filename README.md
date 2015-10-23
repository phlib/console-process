# phlib/console

[![Build Status](https://img.shields.io/travis/phlib/console/master.svg)](https://travis-ci.org/phlib/console)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/console.svg)](https://packagist.org/packages/phlib/console)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/console.svg)](https://packagist.org/packages/phlib/console)

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

## Daemon Command
### Basic Usage

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

## Configuration Helper

Adds the ```-c path/to/config.php``` parameter to the console application and makes it easily accessible to all 
commands. This is most useful for third party libraries which rely on the configuration being specified from the 
options.

### Basic Usage

```php
// your usual cli setup script

use Phlib\Console\Helper\ConfigurationHelper;

$app = new Application('my-cli');
$app->setCommands(['...']);
ConfigurationHelper::initHelper($app, []);
$app->run();

```

```php
class MyCommand extends Command
{
    '...'

    public function createMyObjectInstance()
    {
        $config = $this->getHelper('configuration')->fetch();
        if ($config === false) {
            $config = ['my' => 'defaults'];
        }
        return new MyObjectInstance($config);
    }
}
```

### Configuration
You can specify some options to setup the helper through the ```initHelper``` static method.

|Name|Type|Default|Description|
|----|----|-------|-----------|
|`name`|*String*|`'config'`|The name of the option on the command line.|
|`abbreviation`|*String*|`'c'`|The abbreviation of the option on the command line.|
|`description`|*String*|`'Path to the configuration file.'`|The associated description for the option.|
|`filename`|*String*|`'config.php'`|The filename that will be detected if no name is specified.|

```php
ConfigurationHelper::initHelper($app, [
    'name' => 'config-option',
    'filename' => 'my-cli-config.php',
]);
```
