<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Integrity;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcing\Event\DomainEvents;
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

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    public function addMissingTetheredNodes(NodeType $nodeType, NodeName $tetheredNodeName): CommandResult
    {
        try {
            $tetheredNodeNodeType = $nodeType->getTypeOfAutoCreatedChildNode($tetheredNodeName);
        } catch (NodeTypeNotFoundException $exception) {
            throw new \InvalidArgumentException(sprintf('The tethered node "%s" for type %s has an invalid/unknown type', $tetheredNodeName, $nodeType), 1555051308, $exception);
        }
        if ($tetheredNodeNodeType === null) {
            throw new \InvalidArgumentException(sprintf('There is no tethered node "%s" for type %s according to the schema', $tetheredNodeName, $nodeType), 1555082781);
        }

        $publishedEvents = DomainEvents::createEmpty();
        $nodeTypeName = NodeTypeName::fromString($nodeType->getName());
        foreach ($this->nodesOfType($nodeTypeName) as $contentStreamIdentifier => $nodeAggregate) {
            // FIXME this should probably be filled with ids using NodeAggregateIdentifier::forAutoCreatedChildNode() to be deterministic
            $nodeAggregateIdentifiers = new NodeAggregateIdentifiersByNodePaths([]);
            foreach ($nodeAggregate->getNodesByOccupiedDimensionSpacePoint() as $node) {
                if ($this->tetheredNodeExists($contentStreamIdentifier, $node->getNodeAggregateIdentifier(), $tetheredNodeName)) {
                    continue;
                }
                $events = $this->nodeAggregateCommandHandler->createTetheredChildNode(
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
                $publishedEvents = $publishedEvents->appendEvents($events);
            }
        }
        return CommandResult::fromPublishedEvents($publishedEvents);
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeAggregate[]|\Iterator
     */
    private function nodesOfType(NodeTypeName $nodeTypeName): \Iterator
    {
        $contentStreamIdentifiers = $this->contentGraph->findContentStreamIdentifiers();
        foreach ($contentStreamIdentifiers as $contentStreamIdentifier) {
            $nodeAggregates = $this->contentGraph->findNodeAggregatesByType($contentStreamIdentifier, $nodeTypeName);
            foreach ($nodeAggregates as $nodeAggregate) {
                yield $contentStreamIdentifier => $nodeAggregate;
            }
        }
    }

    private function tetheredNodeExists(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $parentNodeIdentifier, NodeName $tetheredNodeName): bool
    {
        foreach ($this->contentGraph->findTetheredChildNodeAggregates($contentStreamIdentifier, $parentNodeIdentifier) as $tetheredNodeAggregate) {
            // TODO replace with $tetheredNodeAggregate->getNodeName()->equals($tetheredNodeName) once that's available
            if ((string)$tetheredNodeAggregate->getNodeName() === (string)$tetheredNodeName) {
                return true;
            }
        }
        return false;
    }

}
