<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        './Neos.ContentGraph.DoctrineDbalAdapter/src',
        './Neos.ContentRepository.BehavioralTests/Classes',
        './Neos.ContentRepository.BehavioralTests/Tests',
        './Neos.ContentRepository.Core/Classes',
        './Neos.ContentRepository.Core/Tests',
        './Neos.ContentRepository.Dbal/Classes',
        './Neos.ContentRepository.Dbal/Tests',
        './Neos.ContentRepository.Export/src',
        './Neos.ContentRepository.LegacyNodeMigration/Classes',
        './Neos.ContentRepository.LegacyNodeMigration/Tests',
        './Neos.ContentRepository.NodeAccess/Classes',
        './Neos.ContentRepository.NodeAccess/Tests',
        './Neos.ContentRepository.NodeMigration/src',
        './Neos.ContentRepository.StructureAdjustment/src',
        './Neos.ContentRepository.TestSuite/Classes',
        './Neos.ContentRepository.TestSuite/Tests',
        './Neos.ContentRepositoryRegistry/Classes',
        './Neos.ContentRepositoryRegistry/Tests',
        './Neos.ContentRepositoryRegistry.TestSuite/Classes',
        './Neos.Diff/Classes',
        './Neos.Fusion/Classes',
        './Neos.Fusion/Tests',
        './Neos.Fusion.Afx/Classes',
        './Neos.Fusion.Afx/Tests',
        './Neos.Media/Classes',
        './Neos.Media/Tests',
        './Neos.Media.Browser/Classes',
        './Neos.Neos/Classes',
        './Neos.Neos/Tests',
        './Neos.NodeTypes.Form/Classes',
        './Neos.SiteKickstarter/Classes',
        './Neos.SiteKickstarter/Tests',
        './Neos.TimeableNodeVisibility/Classes',
        './Neos.TimeableNodeVisibility/Tests',
        './Neos.Workspace.Ui/Classes',
        './Neos.Workspace.Ui/Tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ]
    ])
    ->setFinder($finder);
