<?php

use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

class SomeClass
{
    public function run(\Neos\ContentRepository\Rector\Legacy\LegacyContextStub $context)
    {
        return $context->getRootNode();
    }
}

?>
-----
<?php

use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

class SomeClass
{
    public function run(\Neos\ContentRepository\Rector\Legacy\LegacyContextStub $context)
    {
        // TODO 9.0 migration: !! MEGA DIRTY CODE! Ensure to rewrite this; by getting rid of LegacyContextStub.

        $contentRepository = $this->contentRepositoryRegistry->get(\Neos\ContentRepository\Factory\ContentRepositoryIdentifier::fromString('default'));
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName(\Neos\ContentRepository\SharedModel\Workspace\WorkspaceName::fromString($context->workspaceName ?? 'live'));
        $rootNodeAggregate = $contentRepository->getContentGraph()->findRootNodeAggregateByType($workspace->currentContentStreamIdentifier, \Neos\ContentRepository\SharedModel\NodeType\NodeTypeName::fromString('Neos.Neos:Sites'));
        $subgraph = $contentRepository->getContentGraph()->getSubgraph($workspace->currentContentStreamIdentifier, \Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint::fromLegacyDimensionArray($context->dimensions ?? []), $context->invisibleContentShown ? \Neos\ContentRepository\SharedModel\VisibilityConstraints::withoutRestrictions() : \Neos\ContentRepository\SharedModel\VisibilityConstraints::frontend());
        return $subgraph->findNodeByNodeAggregateIdentifier($rootNodeAggregate->getIdentifier());
    }
}

?>
