<?php

$finder = PhpCsFixer\Finder::create()
                           ->in([
                               __DIR__ . '/src',
                               __DIR__ . '/tests',
                           ])
                           ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP80Migration:risky' => true,
        '@PHP80Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'void_return' => true,
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
        ],
        'native_constant_invocation' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'multiline_whitespace_before_semicolons' => true,
        'single_quote' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'phpdoc_trim' => true,
        'phpdoc_types_order' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ;