<?php

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjector;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\Flow\Annotations as Flow;
use project\Controller\TodoController;

/**
 * @Flow\Scope("singleton")
 */
class HypergraphObjectFactory
{
    public function __construct(
        private readonly PostgresDbalClientInterface $dbalClient,
        private readonly DbalClientInterface $eventStorageDbalClient,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyConverter $propertyConverter,
        private readonly VariableFrontend $processedEventsCache
    )
    {
    }

    public function  buildContentHypergraph(): ContentHypergraph
    {
        $nodeFactory = new NodeFactory($this->nodeTypeManager, $this->propertyConverter);
        return new ContentHypergraph($this->dbalClient, $nodeFactory);
    }

    public function  buildHypergraphProjector(): HypergraphProjector
    {
        return new HypergraphProjector($this->dbalClient, $this->eventStorageDbalClient, $this->processedEventsCache);
    }
}
