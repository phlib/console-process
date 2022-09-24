<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/example',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $ecsConfig->sets([
        SetList::COMMON,
        SetList::PSR_12,
    ]);

    $ecsConfig->skip([
        // Remove sniff, from common/control-structures
        \PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer::class,

        // Remove sniff, from common/spaces
        \PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class,
        \PhpCsFixer\Fixer\CastNotation\CastSpacesFixer::class,
    ]);
};
