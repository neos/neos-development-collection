<?php
declare (strict_types=1);

use Neos\ContentRepository\Rector\Rules\InjectContentRepositoryRegistryIfNeededRector;
use Neos\ContentRepository\Rector\Rules\NodeGetChildNodesRector;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Transform\Rector\MethodCall\MethodCallToPropertyFetchRector;
use Rector\Transform\ValueObject\MethodCallToPropertyFetch;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Neos\\ContentRepository\\Domain\\Model\\NodeInterface' => Node::class,
        'Neos\\ContentRepository\\Domain\\Projection\\Content\\NodeInterface' => Node::class,
        'Neos\\ContentRepository\\Domain\\Projection\\Content\\TraversableNodeInterface' => Node::class,
    ]);

    $rectorConfig->ruleWithConfiguration(MethodCallToPropertyFetchRector::class, [
        new MethodCallToPropertyFetch(Node::class, 'getIdentifier', 'nodeAggregateIdentifier')
    ]);

    $rectorConfig->rule(NodeGetChildNodesRector::class);

    // Should run LAST - as other rules above might create $this->contentRepositoryRegistry calls.
    $rectorConfig->rule(InjectContentRepositoryRegistryIfNeededRector::class);
};
