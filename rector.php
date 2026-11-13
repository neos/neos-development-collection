<?php
declare (strict_types=1);

use Neos\Rector\NeosRectorSets;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;

return RectorConfig::configure()
    ->withPaths(
        [
            __DIR__ . '/Neos.ContentGraph.DoctrineDbalAdapter/src',
            __DIR__ . '/Neos.ContentRepository.BehavioralTests/Classes',
            __DIR__ . '/Neos.ContentRepository.Core/Classes',
            __DIR__ . '/Neos.ContentRepository.Dbal/Classes',
            __DIR__ . '/Neos.ContentRepository.Export/src',
            __DIR__ . '/Neos.ContentRepository.LegacyNodeMigration/Classes',
            __DIR__ . '/Neos.ContentRepository.NodeAccess/Classes',
            __DIR__ . '/Neos.ContentRepository.NodeMigration/src',
            __DIR__ . '/Neos.ContentRepository.StructureAdjustment/src',
            __DIR__ . '/Neos.ContentRepository.TestSuite/Classes',
            __DIR__ . '/Neos.ContentRepositoryRegistry/Classes',
            __DIR__ . '/Neos.Neos/Classes',
            __DIR__ . '/Neos.Workspace.Ui/Classes',
            __DIR__ . '/Neos.TimeableNodeVisibility/Classes',
            __DIR__ . '/Neos.SiteKickstarter/Classes',
            __DIR__ . '/Neos.NodeTypes.Form/Classes',
            __DIR__ . '/Neos.Fusion/Classes',
            __DIR__ . '/Neos.Media.Browser/Classes',
        ]
    )
    ->withSkip([
        // ignore classes that are manually optimized or generated
        // skip rules that were not non breaky for us
        ReturnNeverTypeRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        ReadOnlyClassRector::class,
        ReadOnlyPropertyRector::class,
        ArrayToFirstClassCallableRector::class,
        SimplifyUselessVariableRector::class,
        RemoveExtraParametersRector::class,
        RemoveUnusedPublicMethodParameterRector::class,
    ])
    // migrate php language features
    ->withPhpSets(
        php84: true
    )
    // remove all obsoletes annotations and unreachable cpde
    ->withDeadCodeLevel(72)
    // annotaions to attributes
    ->withSets([
        NeosRectorSets::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);


