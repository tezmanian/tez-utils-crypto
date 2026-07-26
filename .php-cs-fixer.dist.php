<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0'                             => true,
        '@PHP83Migration'                        => true,
        'ordered_imports'                        => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'                      => true,
        'global_namespace_import'                => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
        'single_quote'                           => true,
        'explicit_string_variable'               => true,
        'array_syntax'                           => ['syntax' => 'short'],
        'trim_array_spaces'                      => true,
        'no_whitespace_before_comma_in_array'    => true,
        'whitespace_after_comma_in_array'        => ['ensure_single_space' => true],
        'declare_strict_types'                   => true,
        'strict_param'                           => true,
        'strict_comparison'                      => true,
        'phpdoc_align'                           => ['align' => 'left'],
        'phpdoc_order'                           => true,
        'phpdoc_trim'                            => true,
        'phpdoc_scalar'                          => true,
        'no_superfluous_phpdoc_tags'             => ['remove_inheritdoc' => true],
        'yoda_style'                             => false,
        'no_useless_else'                        => true,
        'no_useless_return'                      => true,
        'concat_space'                           => ['spacing' => 'one'],
        'trailing_comma_in_multiline'            => ['elements' => ['arrays', 'arguments', 'parameters']],
        'blank_line_before_statement'            => ['statements' => ['return', 'throw', 'try', 'if', 'foreach', 'for', 'while']],
    ])
    ->setFinder($finder);
