<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment;

use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment\Dto\StructureAdjustment;

/**
 * @Flow\Scope("singleton")
 */
class StructureAdjustmentService
{
    protected ContentGraphInterface $contentGraph;
    protected TetheredNodeAdjustments $tetheredNodeAdjustments;

    public function __construct(ContentGraphInterface $contentGraph, TetheredNodeAdjustments $tetheredNodeAdjustments)
    {
        $this->contentGraph = $contentGraph;
        $this->tetheredNodeAdjustments = $tetheredNodeAdjustments;
    }

    /**
     * @return \Generator|StructureAdjustment[]
     */
    public function findAllAdjustments(): \Generator
    {
        foreach ($this->contentGraph->findProjectedNodeTypes() as $nodeTypeName) {
            yield from $this->findAdjustmentsForNodeType($nodeTypeName);
        }
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return \Generator|StructureAdjustment[]
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        yield from $this->tetheredNodeAdjustments->findAdjustmentsForNodeType($nodeTypeName);
    }
}
