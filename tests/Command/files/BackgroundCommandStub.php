<?php

declare(strict_types=1);

require_once __DIR__ . '/ExecuteStubTrait.php';

use Phlib\ConsoleProcess\Command\BackgroundCommand;

class BackgroundCommandStub extends BackgroundCommand
{
    use ExecuteStubTrait;
}
