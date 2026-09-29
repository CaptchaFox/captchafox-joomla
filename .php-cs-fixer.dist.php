<?php

/**
 * Code style: the rules of the Joomla CMS core (PSR-12 plus Joomla conventions).
 */

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/plugin', __DIR__ . '/build', __DIR__ . '/tests'])
    ->append([__FILE__]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules(
        [
            '@PSR12'                                           => true,
            'array_syntax'                                     => ['syntax' => 'short'],
            'no_trailing_comma_in_singleline'                  => true,
            'trailing_comma_in_multiline'                      => ['elements' => ['arrays']],
            'binary_operator_spaces'                           => ['operators' => ['=>' => 'align_single_space_minimal', '=' => 'align', '??=' => 'align']],
            'no_break_comment'                                 => ['comment_text' => 'No break'],
            'no_unused_imports'                                => true,
            'global_namespace_import'                          => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
            'ordered_imports'                                  => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
            'no_useless_else'                                  => true,
            'native_function_invocation'                       => ['include' => ['@compiler_optimized']],
            'nullable_type_declaration_for_default_null_value' => true,
            'no_unneeded_control_parentheses'                  => true,
            'combine_consecutive_issets'                       => true,
            'combine_consecutive_unsets'                       => true,
            'no_useless_sprintf'                               => true,
        ]
    )
    ->setFinder($finder);
