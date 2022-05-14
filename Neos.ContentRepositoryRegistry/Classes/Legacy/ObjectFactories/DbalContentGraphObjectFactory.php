<?php

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ProjectionIntegrityViolationDetector;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class DbalContentGraphObjectFactory
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyConverter $propertyConverter
    )
    {
    }

    public function buildProjectionContentGraph(): ProjectionContentGraph
    {
        return new ProjectionContentGraph($this->dbalClient);
    }

    public function buildProjectionIntegrityViolationDetector(): ProjectionIntegrityViolationDetector
    {
        return new ProjectionIntegrityViolationDetector($this->dbalClient);
    }

    public function buildContentGraph(): ContentGraphInterface
    {
        $nodeFactory = new NodeFactory($this->nodeTypeManager, $this->propertyConverter);
        return new ContentGraph($this->dbalClient, $nodeFactory);
    }
}
