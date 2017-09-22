<?php

namespace Neos\ContentRepository\Domain\Context\Node;

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\ContentRepository\Domain\Context\DimensionSpace\Repository\InterDimensionalFallbackGraph;
use Neos\ContentRepository\Domain\Context\Importing\Command\FinalizeImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Command\StartImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasFinalized;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasStarted;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateRootNode;
use Neos\ContentRepository\Domain\Context\Importing\Command\ImportNode;
use Neos\ContentRepository\Domain\Context\Importing\Event\NodeWasImported;
use Neos\ContentRepository\Domain\Context\Node\Command\MoveNode;
use Neos\ContentRepository\Domain\Context\Node\Command\MoveNodesInAggregate;
use Neos\ContentRepository\Domain\Context\Node\Command\SetNodeProperty;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Domain\Context\Node\Event\NodePropertyWasSet;
use Neos\ContentRepository\Domain\Context\Node\Event\NodesInAggregateWereMoved;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeWasMoved;
use Neos\ContentRepository\Domain\Context\Node\Event\RootNodeWasCreated;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
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
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
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
        $this->eventPublisher->publishMany(ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()),
            $events);
    }

    /**
     * @param StartImportingSession $command
     */
    public function handleStartImportingSession(StartImportingSession $command): void
    {
        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->eventPublisher->publish($streamName,
            new ImportingSessionWasStarted($command->getImportingSessionIdentifier()), ExpectedVersion::NO_STREAM);
    }

    /**
     * @param ImportNode $command
     */
    public function handleImportNode(ImportNode $command): void
    {
        $this->validateNodeTypeName($command->getNodeTypeName());

        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->eventPublisher->publish($streamName, new NodeWasImported(
            $command->getImportingSessionIdentifier(),
            $command->getParentNodeIdentifier(),
            $command->getNodeIdentifier(),
            $command->getNodeName(),
            $command->getNodeTypeName(),
            $command->getDimensionValues(),
            $command->getPropertyValues()
        ));
    }

    /**
     * @param FinalizeImportingSession $command
     */
    public function handleFinalizeImportingSession(FinalizeImportingSession $command): void
    {
        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->eventPublisher->publish($streamName,
            new ImportingSessionWasFinalized($command->getImportingSessionIdentifier()));
    }

    /**
     * Create events for adding a node aggregate with node, including all auto-created child node aggregates with nodes (recursively)
     *
     * @param CreateNodeAggregateWithNode $command
     * @return array
     * @throws DimensionSpacePointNotFound
     */
    private function nodeAggregateWithNodeWasCreatedFromCommand(CreateNodeAggregateWithNode $command): array
    {
        $nodeType = $this->getNodeType($command->getNodeTypeName());

        $propertyDefaultValuesAndTypes = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
            $propertyDefaultValuesAndTypes[$propertyName] = new PropertyValue($propertyValue,
                $nodeType->getPropertyType($propertyName));
        }

        $events = [];

        $dimensionSpacePoint = $command->getDimensionSpacePoint();

        // TODO Validate if node with parentNodeIdentifier is visible in the subgraph with contentStreamIdentifier, dimensionSpacePoint

        $visibleDimensionSpacePoints = $this->getVisibleDimensionSpacePoints($dimensionSpacePoint);

        $events[] = new NodeAggregateWithNodeWasCreated(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier(),
            $command->getNodeTypeName(),
            $dimensionSpacePoint,
            $visibleDimensionSpacePoints,
            $command->getNodeIdentifier(),
            $command->getParentNodeIdentifier(),
            $command->getNodeName(),
            $propertyDefaultValuesAndTypes
        );

        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            $childNodeName = new NodeName($childNodeNameStr);
            $childNodeAggregateIdentifier = NodeAggregateIdentifier::forAutoCreatedChildNode($childNodeName,
                $command->getNodeAggregateIdentifier());
            // FIXME This auto-created child node identifier is random but should match the created child node persistence identifier of NodeData for the legacy layer
            $childNodeIdentifier = new NodeIdentifier();
            $childParentNodeIdentifier = $command->getNodeIdentifier();

            $events = array_merge($events,
                $this->nodeAggregateWithNodeWasCreatedFromCommand(new CreateNodeAggregateWithNode(
                    $command->getContentStreamIdentifier(),
                    $childNodeAggregateIdentifier,
                    new NodeTypeName($childNodeType),
                    $dimensionSpacePoint,
                    $childNodeIdentifier,
                    $childParentNodeIdentifier,
                    $childNodeName
                )));
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
        $this->eventPublisher->publish(
            ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()),
            new RootNodeWasCreated(
                $command->getContentStreamIdentifier(),
                $command->getNodeIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        );
    }

    /**
     * @param SetNodeProperty $command
     */
    public function handleSetNodeProperty(SetNodeProperty $command): void
    {
        $nodeType = $this->getNodeType($command->getNodeTypeName());
        $propertyType = $nodeType->getPropertyType($command->getPropertyName());

        $propertyValue = new PropertyValue($command->getValue(), $propertyType);

        $event = new NodePropertyWasSet(
            $command->getContentStreamIdentifier(),
            $command->getNodeIdentifier(),
            $command->getPropertyName(),
            $propertyValue
        );

        $this->eventPublisher->publish(ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()),
            $event);
    }

    /**
     * @param MoveNode $command
     */
    public function handleMoveNode(MoveNode $command): void
    {
        $contentStreamIdentifier = $command->getContentStreamIdentifier();

        /** @var Node $node */
        $node = $this->getNode($contentStreamIdentifier, $command->getNodeIdentifier());

        $contentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $node->dimensionSpacePoint);
        if ($contentSubgraph === null) {
            throw new Exception(sprintf('Content subgraph not found for content stream %s, %s', $contentStreamIdentifier, $node->dimensionSpacePoint), 1506074858);
        }

        $referenceNode = $contentSubgraph->findNodeByIdentifier($command->getReferenceNodeIdentifier());
        if ($referenceNode === null) {
            throw new NodeNotFoundException(sprintf('Reference node %s not found for content stream %s, %s', $command->getReferenceNodeIdentifier(), $contentStreamIdentifier, $node->dimensionSpacePoint), 1506075821, $command->getReferenceNodeIdentifier());
        }

        $event = new NodeWasMoved(
            $command->getContentStreamIdentifier(),
            $command->getNodeIdentifier(),
            $command->getReferencePosition(),
            $command->getReferenceNodeIdentifier()
        );

        $this->eventPublisher->publish(ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()), $event);
    }

    /**
     * @param MoveNodesInAggregate $command
     */
    public function handleMoveNodesInAggregate(MoveNodesInAggregate $command): void
    {
        // TODO Get nodes in nodeAggregateIdentifier
        // TODO Check: foreach node we can find a node in the content subgraph with node.dimensionSpacePoint by referenced aggregated node identifier

        $event = new NodesInAggregateWereMoved(
          $command->getContentStreamIdentifier(),
          $command->getNodeAggregateIdentifier(),
          $command->getReferencePosition(),
          $command->getReferenceNodeAggregateIdentifier()
        );

        $this->eventPublisher->publish(ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()), $event);
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
        $contentSubgraph = $this->interDimensionalFallbackGraph->getSubgraphByDimensionSpacePoint($dimensionSpacePoint);
        if ($contentSubgraph === null) {
            throw new DimensionSpacePointNotFound(sprintf('%s was not found in the allowed dimension subspace',
                $dimensionSpacePoint), 1505929456);
        }
        $points = [$dimensionSpacePoint];
        foreach ($contentSubgraph->getVariants() as $variant) {
            $points[] = $variant->getIdentifier();
        }
        $visibleDimensionSpacePoints = new DimensionSpacePointSet($points);
        return $visibleDimensionSpacePoints;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeInterface
     * @throws NodeNotFoundException
     */
    private function getNode(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier): NodeInterface
    {
        $node = $this->contentGraph->findNodeByIdentifierInContentStream($contentStreamIdentifier, $nodeIdentifier);
        if ($node === null) {
            throw new NodeNotFoundException(sprintf('Node %s not found', $nodeIdentifier), 1506074496, $nodeIdentifier);
        }
        return $node;
    }
}
