<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Integrity;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class IntegrityViolationResolver
{

    /**
     * @Flow\Inject
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * @Flow\Inject
     * @var IntegrityViolationDetector
     */
    protected $integrityViolationDetector;

    /**
     * @Flow\Inject
     * @var EventStoreManager
     */
    protected $eventStoreManager;

    public function addMissingTetheredNodes(NodeType $nodeType, NodeName $tetheredNodeName)
    {
        try {
            $tetheredNodeNodeType = $nodeType->getTypeOfAutoCreatedChildNode($tetheredNodeName);
        } catch (NodeTypeNotFoundException $exception) {
            throw new \InvalidArgumentException(sprintf('There is no tethered node "%s" for type %s according to the schema', $tetheredNodeName, $nodeType), 1555051308, $exception);
        }
        if ($tetheredNodeNodeType === null) {
            throw new \InvalidArgumentException(sprintf('There is no tethered node "%s" for type %s according to the schema', $tetheredNodeName, $nodeType), 1555082781);
        }

        $nodeTypeName = NodeTypeName::fromString($nodeType->getName());
        foreach ($this->integrityViolationDetector->nodesOfType($nodeTypeName) as $contentStreamIdentifier => $nodeAggregate) {
            // FIXME this should probably be filled with ids using NodeAggregateIdentifier::forAutoCreatedChildNode() to be deterministic
            $nodeAggregateIdentifiers = new NodeAggregateIdentifiersByNodePaths([]);
            foreach ($nodeAggregate->getNodes() as $node) {
                $events = $this->nodeAggregateCommandHandler->handleTetheredChildNode(
                    $contentStreamIdentifier,
                    $nodeAggregate->getCoveredDimensionSpacePoints(),
                    $node->getOriginDimensionSpacePoint(),
                    $node->getNodeAggregateIdentifier(),
                    $nodeAggregateIdentifiers,
                    $tetheredNodeName,
                    $tetheredNodeNodeType
                );
                $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);
                $eventStore = $this->eventStoreManager->getEventStoreForStreamName($contentStreamEventStreamName->getEventStreamName());
                $eventStore->commit($contentStreamEventStreamName->getEventStreamName(), $events);
            }
        }
    }

}
