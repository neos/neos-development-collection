<?php

namespace Neos\ContentRepository\Domain\Context\Node;

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\ContentRepository\Domain\Context\DimensionSpace\Repository\InterDimensionalFallbackGraph;
use Neos\ContentRepository\Domain\Context\Importing\Command\FinalizeImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Command\StartImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasFinalized;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasStarted;
use Neos\ContentRepository\Domain\Context\Node\Command\TranslateNodeInAggregate;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateRootNode;
use Neos\ContentRepository\Domain\Context\Importing\Command\ImportNode;
use Neos\ContentRepository\Domain\Context\Importing\Event\NodeWasImported;
use Neos\ContentRepository\Domain\Context\Node\Command\MoveNode;
use Neos\ContentRepository\Domain\Context\Node\Command\MoveNodesInAggregate;
use Neos\ContentRepository\Domain\Context\Node\Command\ChangeNodeName;
use Neos\ContentRepository\Domain\Context\Node\Command\SetNodeProperty;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeNameWasChanged;
use Neos\ContentRepository\Domain\Context\Node\Event\NodePropertyWasSet;
use Neos\ContentRepository\Domain\Context\Node\Event\NodesInAggregateWereMoved;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeWasMoved;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeInAggregateWasTranslated;
use Neos\ContentRepository\Domain\Context\Node\Event\RootNodeWasCreated;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
use Neos\ContentRepository\Exception;
use Neos\ContentRepository\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Exception\NodeNotFoundException;
use Neos\EventSourcing\Event\EventPublisher;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\Flow\Annotations as Flow;

final class NodeCommandHandler
{

    /**
     * @Flow\Inject
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var InterDimensionalFallbackGraph
     */
    protected $interDimensionalFallbackGraph;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Projection\Content\ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @param CreateNodeAggregateWithNode $command
     */
    public function handleCreateNodeAggregateWithNode(CreateNodeAggregateWithNode $command): void
    {
        $events = $this->nodeAggregateWithNodeWasCreatedFromCommand($command);
        $this->eventPublisher->publishMany(
            ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()),
            $events
        );
    }

    /**
     * @param StartImportingSession $command
     */
    public function handleStartImportingSession(StartImportingSession $command): void
    {
        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->eventPublisher->publish(
            $streamName,
            new ImportingSessionWasStarted($command->getImportingSessionIdentifier()),
            ExpectedVersion::NO_STREAM
        );
    }

    /**
     * @param ImportNode $command
     */
    public function handleImportNode(ImportNode $command): void
    {
        $this->validateNodeTypeName($command->getNodeTypeName());

        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->eventPublisher->publish(
            $streamName,
            new NodeWasImported(
                $command->getImportingSessionIdentifier(),
                $command->getParentNodeIdentifier(),
                $command->getNodeIdentifier(),
                $command->getNodeName(),
                $command->getNodeTypeName(),
                $command->getDimensionValues(),
                $command->getPropertyValues()
            )
        );
    }

    /**
     * @param FinalizeImportingSession $command
     */
    public function handleFinalizeImportingSession(FinalizeImportingSession $command): void
    {
        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->eventPublisher->publish(
            $streamName,
            new ImportingSessionWasFinalized($command->getImportingSessionIdentifier())
        );
    }

    /**
     * Create events for adding a node aggregate with node, including all auto-created child node aggregates with nodes (recursively)
     *
     * @param CreateNodeAggregateWithNode $command
     * @param bool $checkParent
     * @return array
     * @throws Exception
     * @throws NodeNotFoundException
     */
    private function nodeAggregateWithNodeWasCreatedFromCommand(CreateNodeAggregateWithNode $command, bool $checkParent = true): array
    {
        $nodeType = $this->getNodeType($command->getNodeTypeName());

        $propertyDefaultValuesAndTypes = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
            $propertyDefaultValuesAndTypes[$propertyName] = new PropertyValue($propertyValue,
                $nodeType->getPropertyType($propertyName));
        }

        $events = [];

        $dimensionSpacePoint = $command->getDimensionSpacePoint();
        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        $parentNodeIdentifier = $command->getParentNodeIdentifier();

        if ($checkParent) {
            $contentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier,
                $dimensionSpacePoint);
            if ($contentSubgraph === null) {
                throw new Exception(sprintf('Content subgraph not found for content stream %s, %s',
                    $contentStreamIdentifier, $dimensionSpacePoint), 1506440320);
            }
            $parentNode = $contentSubgraph->findNodeByIdentifier($parentNodeIdentifier);
            if ($parentNode === null) {
                throw new NodeNotFoundException(sprintf('Parent node %s not found for content stream %s, %s',
                    $parentNodeIdentifier, $contentStreamIdentifier, $dimensionSpacePoint),
                    1506440451, $parentNodeIdentifier);
            }
        }

        $visibleDimensionSpacePoints = $this->getVisibleDimensionSpacePoints($dimensionSpacePoint);

        $events[] = new NodeAggregateWithNodeWasCreated(
            $contentStreamIdentifier,
            $command->getNodeAggregateIdentifier(),
            $command->getNodeTypeName(),
            $dimensionSpacePoint,
            $visibleDimensionSpacePoints,
            $command->getNodeIdentifier(),
            $parentNodeIdentifier,
            $command->getNodeName(),
            $propertyDefaultValuesAndTypes
        );

        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            $childNodeName = new NodeName($childNodeNameStr);
            $childNodeAggregateIdentifier = NodeAggregateIdentifier::forAutoCreatedChildNode($childNodeName, $command->getNodeAggregateIdentifier());
            $childNodeIdentifier = new NodeIdentifier();
            $childParentNodeIdentifier = $command->getNodeIdentifier();

            $events = array_merge($events,
                $this->nodeAggregateWithNodeWasCreatedFromCommand(new CreateNodeAggregateWithNode(
                    $contentStreamIdentifier,
                    $childNodeAggregateIdentifier,
                    new NodeTypeName($childNodeType),
                    $dimensionSpacePoint,
                    $childNodeIdentifier,
                    $childParentNodeIdentifier,
                    $childNodeName
                ), false));
        }

        return $events;
    }

    /**
     * CreateRootNode
     *
     * @param CreateRootNode $command
     */
    public function handleCreateRootNode(CreateRootNode $command): void
    {
        $contentStreamIdentifier = $command->getContentStreamIdentifier();

        $event = new RootNodeWasCreated(
            $contentStreamIdentifier,
            $command->getNodeIdentifier(),
            $command->getInitiatingUserIdentifier()
        );

        $this->eventPublisher->publish(
            ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
            $event
        );
    }

    /**
     * @param SetNodeProperty $command
     */
    public function handleSetNodeProperty(SetNodeProperty $command): void
    {
        $contentStreamIdentifier = $command->getContentStreamIdentifier();

        // Check if node exists
        $this->getNode($contentStreamIdentifier, $command->getNodeIdentifier());

        $event = new NodePropertyWasSet(
            $contentStreamIdentifier,
            $command->getNodeIdentifier(),
            $command->getPropertyName(),
            $command->getValue()
        );

        $this->eventPublisher->publish(
            ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
            $event
        );
    }

    /**
     * @param MoveNode $command
     * @throws Exception
     * @throws NodeNotFoundException
     */
    public function handleMoveNode(MoveNode $command): void
    {
        $contentStreamIdentifier = $command->getContentStreamIdentifier();

        /** @var Node $node */
        $node = $this->getNode($contentStreamIdentifier, $command->getNodeIdentifier());

        $contentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier,
            $node->dimensionSpacePoint);
        if ($contentSubgraph === null) {
            throw new Exception(sprintf('Content subgraph not found for content stream %s, %s',
                $contentStreamIdentifier, $node->dimensionSpacePoint), 1506074858);
        }

        $referenceNode = $contentSubgraph->findNodeByIdentifier($command->getReferenceNodeIdentifier());
        if ($referenceNode === null) {
            throw new NodeNotFoundException(sprintf('Reference node %s not found for content stream %s, %s',
                $command->getReferenceNodeIdentifier(), $contentStreamIdentifier, $node->dimensionSpacePoint),
                1506075821, $command->getReferenceNodeIdentifier());
        }

        $event = new NodeWasMoved(
            $command->getContentStreamIdentifier(),
            $command->getNodeIdentifier(),
            $command->getReferencePosition(),
            $command->getReferenceNodeIdentifier()
        );

        $this->eventPublisher->publish(
            ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
            $event
        );
    }

    /**
     * @param MoveNodesInAggregate $command
     * @throws Exception
     */
    public function handleMoveNodesInAggregate(MoveNodesInAggregate $command): void
    {
        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        $nodeAggregateIdentifier = $command->getNodeAggregateIdentifier();
        $referenceNodeAggregateIdentifier = $command->getReferenceNodeAggregateIdentifier();
        $sourceNodes = $this->contentGraph->findNodesByNodeAggregateIdentifier($contentStreamIdentifier, $nodeAggregateIdentifier);
        $nodesToReferenceNodes = [];

        /** @var Node $sourceNode */
        foreach ($sourceNodes as $sourceNode) {
            $dimensionSpacePoint = $sourceNode->dimensionSpacePoint;
            $contentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $dimensionSpacePoint);
            if ($contentSubgraph === null) {
                throw new Exception(sprintf('Content subgraph not found for content stream %s, %s', $contentStreamIdentifier, $dimensionSpacePoint), 1506439819);
            }
            /** @var Node $referenceNode */
            $referenceNode = $contentSubgraph->findNodeByNodeAggregateIdentifier($referenceNodeAggregateIdentifier);
            if ($referenceNode === null) {
                throw new Exception(sprintf('No node found for reference node aggregate %s in content stream %s, %s',
                    $referenceNodeAggregateIdentifier, $contentStreamIdentifier, $dimensionSpacePoint), 1506439842);
            }
            // TODO Introduce a mapping value object with checks for uniqueness
            $nodesToReferenceNodes[(string)$sourceNode->identifier] = (string)$referenceNode->identifier;
        }

        $event = new NodesInAggregateWereMoved(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $command->getReferencePosition(),
            $referenceNodeAggregateIdentifier,
            $nodesToReferenceNodes
        );

        $this->eventPublisher->publish(
            ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
            $event
        );
    }

    /**
     * @param ChangeNodeName $command
     * @throws Exception\NodeException
     */
    public function handleChangeNodeName(ChangeNodeName $command)
    {
        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        /** @var Node $node */
        $node = $this->getNode($contentStreamIdentifier, $command->getNodeIdentifier());

        if ($node->getNodeType()->getName() === 'Neos.ContentRepository:Root') {
            throw new Exception\NodeException('The root node cannot be renamed.', 1346778388);
        }

        $event = new NodeNameWasChanged(
            $contentStreamIdentifier,
            $command->getNodeIdentifier(),
            $command->getNewNodeName()
        );

        $this->eventPublisher->publish(
            ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
            $event
        );
    }

    /**
     * @param TranslateNodeInAggregate $command
     * @throws Exception\NodeException
     */
    public function handleTranslateNodeInAggregate(TranslateNodeInAggregate $command): void
    {

        $contentStreamIdentifier = $command->getContentStreamIdentifier();

        $events = $this->nodeInAggregateWasTranslatedFromCommand($command);
        $this->eventPublisher->publishMany(
            ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
            $events
        );
    }

    private function nodeInAggregateWasTranslatedFromCommand(TranslateNodeInAggregate $command): array {
        $sourceNodeIdentifier = $command->getSourceNodeIdentifier();
        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        $dimensionSpacePoint = $command->getDimensionSpacePoint();
        $destinationNodeIdentifier = $command->getDestinationNodeIdentifier();

        $sourceNode = $this->getNode($contentStreamIdentifier, $sourceNodeIdentifier);

        // TODO Check that command->dimensionSpacePoint is not a generalization or specialization of sourceNode->dimensionSpacePoint!!! (translation)

        $sourceContentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $sourceNode->dimensionSpacePoint);
        /** @var Node $sourceParentNode */
        $sourceParentNode = $sourceContentSubgraph->findParentNode($sourceNodeIdentifier);
        if ($sourceParentNode === null) {
            throw new Exception\NodeException(sprintf('Parent node for %s in %s not found',
                $sourceNodeIdentifier, $sourceNode->dimensionSpacePoint), 1506354274);
        }

        if ($command->getDestinationParentNodeIdentifier() !== null) {
            $destinationParentNodeIdentifier = $command->getDestinationParentNodeIdentifier();
        } else {
            $parentNodeAggregateIdentifier = $sourceParentNode->aggregateIdentifier;
            $destinationContentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier,
                $dimensionSpacePoint);
            /** @var Node $destinationParentNode */
            $destinationParentNode = $destinationContentSubgraph->findNodeByNodeAggregateIdentifier($parentNodeAggregateIdentifier);
            if ($destinationParentNode === null) {
                throw new Exception\NodeException(sprintf('Could not find suitable parent node for %s in %s',
                    $sourceNodeIdentifier, $destinationContentSubgraph->getDimensionSpacePoint()), 1506354275);
            }
            $destinationParentNodeIdentifier = $destinationParentNode->identifier;
        }

        $dimensionSpacePointSet = $this->getVisibleDimensionSpacePoints($dimensionSpacePoint);

        $events[] = new NodeInAggregateWasTranslated(
            $contentStreamIdentifier,
            $sourceNodeIdentifier,
            $destinationNodeIdentifier,
            $destinationParentNodeIdentifier,
            $dimensionSpacePoint,
            $dimensionSpacePointSet
        );

        // TODO Add a recursive flag and translate _all_ child nodes in this case
        foreach ($sourceNode->getNodeType()->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            /** @var Node $childNode */
            $childNode = $sourceContentSubgraph->findChildNodeConnectedThroughEdgeName($sourceNodeIdentifier, new NodeName($childNodeNameStr));
            if ($childNode === null) {
                throw new Exception\NodeException(sprintf('Could not find auto-created child node with name %s for %s in %s',
                    $childNodeNameStr, $sourceNodeIdentifier, $sourceNode->dimensionSpacePoint), 1506506170);
            }

            $childDestinationNodeIdentifier = new NodeIdentifier();
            $childDestinationParentNodeIdentifier = $destinationNodeIdentifier;
            $events = array_merge($events,
                $this->nodeInAggregateWasTranslatedFromCommand(new TranslateNodeInAggregate(
                    $contentStreamIdentifier,
                    $childNode->identifier,
                    $childDestinationNodeIdentifier,
                    $dimensionSpacePoint,
                    $childDestinationParentNodeIdentifier
                )));
        }

        return $events;
    }


    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeType
     */
    private function getNodeType(NodeTypeName $nodeTypeName): NodeType
    {
        $this->validateNodeTypeName($nodeTypeName);

        $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeTypeName);
        return $nodeType;
    }

    /**
     * @param NodeTypeName $nodeTypeName
     */
    private function validateNodeTypeName(NodeTypeName $nodeTypeName): void
    {
        if (!$this->nodeTypeManager->hasNodeType((string)$nodeTypeName)) {
            throw new \InvalidArgumentException('TODO: Node type ' . $nodeTypeName . ' not found.');
        }
    }

    /**
     * @param $dimensionSpacePoint
     * @return DimensionSpacePointSet
     * @throws DimensionSpacePointNotFound
     */
    private function getVisibleDimensionSpacePoints($dimensionSpacePoint): DimensionSpacePointSet
    {
        return $this->interDimensionalFallbackGraph->getSpecializationSet($dimensionSpacePoint);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @return Node
     * @throws NodeNotFoundException
     */
    private function getNode(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier): Node
    {
        /** @var Node $node */
        $node = $this->contentGraph->findNodeByIdentifierInContentStream($contentStreamIdentifier, $nodeIdentifier);
        if ($node === null) {
            throw new NodeNotFoundException(sprintf('Node %s not found', $nodeIdentifier), 1506074496, $nodeIdentifier);
        }
        return $node;
    }

}
