<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        './Neos.ContentGraph.DoctrineDbalAdapter/src',
        './Neos.ContentGraph.PostgreSQLAdapter/src',
        './Neos.ContentRepository.BehavioralTests/Classes',
        './Neos.ContentRepository.TestSuite/Classes',
        './Neos.ContentRepository.Core/Classes',
        './Neos.Neos/Classes',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ]
    ])
    ->setFinder($finder);
