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
use Neos\EventSourcedContentRepository\Domain\Context\Integrity\Command\AddMissingTetheredNodes;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\Exception as PropertyException;
use Neos\Flow\Security\Exception as SecurityException;
use Ramsey\Uuid\Uuid;

/**
 * @Flow\Scope("singleton")
 */
final class IntegrityViolationCommandHandler
{

    /**
     * @var NodeAggregateEventPublisher
     */
    private $nodeAggregateEventPublisher;

    /**
     * @var ContentGraphInterface
     */
    private $contentGraph;

    public function __construct(NodeAggregateEventPublisher $nodeAggregateEventPublisher, ContentGraphInterface $contentGraph)
    {
        $this->nodeAggregateEventPublisher = $nodeAggregateEventPublisher;
        $this->contentGraph = $contentGraph;
    }

    /**
     * @param AddMissingTetheredNodes $command
     * @return CommandResult
     * @throws PropertyException | SecurityException
     */
    public function handleAddMissingTetheredNodes(AddMissingTetheredNodes $command): CommandResult
    {
        $nodeType = $command->getNodeType();
        $tetheredNodeName = $command->getTetheredNodeName();
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
        $initialTetheredNodePropertyValues = $this->getDefaultPropertyValues($tetheredNodeNodeType);
        foreach ($this->nodesOfType($nodeTypeName) as $contentStreamIdentifier => $nodeAggregate) {
            $tetheredNodeAggregateIdentifier = NodeAggregateIdentifier::forAutoCreatedChildNode($tetheredNodeName, $nodeAggregate->getIdentifier());
            foreach ($nodeAggregate->getNodesByOccupiedDimensionSpacePoint() as $node) {
                if ($this->tetheredNodeExists($contentStreamIdentifier, $node->getNodeAggregateIdentifier(), $tetheredNodeName)) {
                    continue;
                }
                $succeedingNodeAggregateIdentifier = null;
                $succeedingTetheredNodeName = $this->getSucceedingTetheredNodeName($nodeType, $tetheredNodeName);
                if ($succeedingTetheredNodeName !== null) {
                    $subgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $node->getOriginDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
                    if ($subgraph !== null) {
                        $succeedingNode = $subgraph->findChildNodeConnectedThroughEdgeName($nodeAggregate->getIdentifier(), $succeedingTetheredNodeName);
                        if ($succeedingNode !== null) {
                            $succeedingNodeAggregateIdentifier = $succeedingNode->getNodeAggregateIdentifier();
                        }
                    }
                }
                $event = DecoratedEvent::addIdentifier(new NodeAggregateWithNodeWasCreated(
                    $contentStreamIdentifier,
                    $tetheredNodeAggregateIdentifier,
                    NodeTypeName::fromString($tetheredNodeNodeType->getName()),
                    $node->getOriginDimensionSpacePoint(),
                    $nodeAggregate->getCoveredDimensionSpacePoints(),
                    $nodeAggregate->getIdentifier(),
                    $tetheredNodeName,
                    $initialTetheredNodePropertyValues,
                    NodeAggregateClassification::tethered(),
                    $succeedingNodeAggregateIdentifier
                ), Uuid::uuid4()->toString());

                $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);
                $this->nodeAggregateEventPublisher->publish($contentStreamEventStreamName->getEventStreamName(), $event);
                $publishedEvents = $publishedEvents->appendEvent($event);
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
        $contentStreamIdentifiers = $this->contentGraph->findProjectedContentStreamIdentifiers();
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

    private function getSucceedingTetheredNodeName(NodeType $nodeType, NodeName $tetheredNodeName): ?NodeName
    {
        $tetheredNodeNames = array_keys($nodeType->getAutoCreatedChildNodes());
        $index = array_search((string)$tetheredNodeName, $tetheredNodeNames, true);
        if ($index === false || !array_key_exists($index + 1, $tetheredNodeNames)) {
            return null;
        }
        return NodeName::fromString($tetheredNodeNames[$index + 1]);
    }

    private function getDefaultPropertyValues(NodeType $nodeType): SerializedPropertyValues
    {
        $rawDefaultPropertyValues = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $defaultValue) {
            $rawDefaultPropertyValues[$propertyName] = [
                'type' => $nodeType->getPropertyType($propertyName),
                'value' => $defaultValue
            ];
        }

        return SerializedPropertyValues::fromArray($rawDefaultPropertyValues);
    }
}
