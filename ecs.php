<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()

    ->withSets([
        SetList::COMMON,
        SetList::PSR_12,
        SetList::STRICT,
    ])

    ->withSkip([
        // Remove sniff, from common/control-structures
        \PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer::class,

        // Remove sniff, from common/spaces
        \PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class,
    ])

    // Rule from common/spaces. No space after cast.
    ->withConfiguredRule(
        \PhpCsFixer\Fixer\CastNotation\CastSpacesFixer::class,
        [
            'space' => 'none',
        ],
    )

    /*
     * Rule missing from PSR12. PER Coding Style 3:
     * "... each of the blocks [of import statements] MUST be separated by a single blank line ..."
     */
    ->withRules([
        \PhpCsFixer\Fixer\Whitespace\BlankLineBetweenImportGroupsFixer::class,
    ])

    // Rule from PSR12. PER Coding Style 7.1: "The `fn` keyword MUST NOT be succeeded by a space."
    ->withConfiguredRule(
        \PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer::class,
        [
            'closure_fn_spacing' => 'none',
        ],
    );
