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
    protected UnknownNodeTypeAdjustment $unknownNodeTypeAdjustment;
    protected DisallowedChildNodeAdjustment $disallowedChildNodeAdjustment;
    protected PropertyAdjustment $propertyAdjustment;

    public function __construct(ContentGraphInterface $contentGraph, TetheredNodeAdjustments $tetheredNodeAdjustments, UnknownNodeTypeAdjustment $unknownNodeTypeAdjustment, DisallowedChildNodeAdjustment $disallowedChildNodeAdjustment, PropertyAdjustment $propertyAdjustment)
    {
        $this->contentGraph = $contentGraph;
        $this->tetheredNodeAdjustments = $tetheredNodeAdjustments;
        $this->unknownNodeTypeAdjustment = $unknownNodeTypeAdjustment;
        $this->disallowedChildNodeAdjustment = $disallowedChildNodeAdjustment;
        $this->propertyAdjustment = $propertyAdjustment;
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
        yield from $this->unknownNodeTypeAdjustment->findAdjustmentsForNodeType($nodeTypeName);
        yield from $this->disallowedChildNodeAdjustment->findAdjustmentsForNodeType($nodeTypeName);
        yield from $this->propertyAdjustment->findAdjustmentsForNodeType($nodeTypeName);
    }
}
