<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment;

use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment\Traits\RemoveNodeAggregateTrait;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment\Dto\StructureAdjustment;
use Neos\EventSourcing\EventStore\EventStore;

/**
 * @Flow\Scope("singleton")
 */
class UnknownNodeTypeAdjustment
{
    use RemoveNodeAggregateTrait;

    protected EventStore $eventStore;
    protected ProjectedNodeIterator $projectedNodeIterator;
    protected NodeTypeManager $nodeTypeManager;

    public function __construct(EventStore $eventStore, ProjectedNodeIterator $projectedNodeIterator, NodeTypeManager $nodeTypeManager)
    {
        $this->eventStore = $eventStore;
        $this->projectedNodeIterator = $projectedNodeIterator;
        $this->nodeTypeManager = $nodeTypeManager;
    }

    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        try {
            $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeTypeName);
            if ($nodeType->getName() !== $nodeTypeName->jsonSerialize()) {
                // the $nodeTypeName was different than the fetched node type; so that means
                // that the FallbackNodeType has been returned.
                yield from $this->removeAllNodesOfType($nodeTypeName);
            }
        } catch (NodeTypeNotFoundException $e) {
            // the $nodeTypeName was not found; so we need to remove all nodes of this type.
            // This case applies if the fallbackNodeType is not configured.
            yield from $this->removeAllNodesOfType($nodeTypeName);
        }
    }

    protected function getEventStore(): EventStore
    {
        return $this->eventStore;
    }

    private function removeAllNodesOfType(NodeTypeName $nodeTypeName): \Generator
    {
        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            yield StructureAdjustment::createForNodeAggregate($nodeAggregate, StructureAdjustment::NODE_TYPE_MISSING, 'The node type "' . $nodeTypeName->jsonSerialize() . '" is not found; so the node should be removed (or converted)', function() use ($nodeAggregate) {
                return $this->removeNodeAggregate($nodeAggregate);
            });
        }
    }
}
