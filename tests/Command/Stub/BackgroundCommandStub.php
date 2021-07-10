<?php

declare(strict_types=1);

namespace Phlib\ConsoleProcess\Command\Stub;

use Phlib\ConsoleProcess\Command\BackgroundCommand;

class BackgroundCommandStub extends BackgroundCommand
{
    use ExecuteStubTrait;
}
