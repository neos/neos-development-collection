<?php

namespace Neos\ContentRepository\Domain\Context\Node;

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\ContentRepository\Domain\Context\Importing\Command\FinalizeImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Command\StartImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasFinalized;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasStarted;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateRootNode;
use Neos\ContentRepository\Domain\Context\Importing\Command\ImportNode;
use Neos\ContentRepository\Domain\Context\Importing\Event\NodeWasImported;
use Neos\ContentRepository\Domain\Context\Node\Command\SetProperty;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Domain\Context\Node\Event\PropertyWasSet;
use Neos\ContentRepository\Domain\Context\Node\Event\RootNodeWasCreated;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\Flow\Annotations as Flow;

final class NodeCommandHandler
{
    /**
     * @Flow\Inject
     * @var \Neos\EventSourcing\Event\EventPublisher
     */
    protected $eventPublisher;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @param CreateNodeAggregateWithNode $command
     */
    public function handleCreateNodeAggregateWithNode(CreateNodeAggregateWithNode $command)
    {
        $events = $this->nodeAggregateWithNodeWasCreatedFromCommand($command);
        $this->eventPublisher->publishMany(ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()), $events);
    }

    /**
     * @param StartImportingSession $command
     */
    public function handleStartImportingSession(StartImportingSession $command)
    {
        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->eventPublisher->publish($streamName, new ImportingSessionWasStarted($command->getImportingSessionIdentifier()), ExpectedVersion::NO_STREAM);
    }

    /**
     * @param ImportNode $command
     */
    public function handleImportNode(ImportNode $command)
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
    public function handleFinalizeImportingSession(FinalizeImportingSession $command)
    {
        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->eventPublisher->publish($streamName, new ImportingSessionWasFinalized($command->getImportingSessionIdentifier()));
    }

    /**
     * Create events for adding a node aggregate with node, including all auto-created child node aggregates with nodes (recursively)
     *
     * @param CreateNodeAggregateWithNode $command
     * @return array
     * @throws NodeTypeNotFoundException
     */
    private function nodeAggregateWithNodeWasCreatedFromCommand(CreateNodeAggregateWithNode $command): array
    {
        if (empty($command->getNodeTypeName())) {
            throw new \InvalidArgumentException('TODO: Node type may not be null');
        }
        $nodeType = $this->getNodeType($command->getNodeTypeName());

        $propertyDefaultValuesAndTypes = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
            $propertyDefaultValuesAndTypes[$propertyName] = new PropertyValue($propertyValue,
                $nodeType->getPropertyType($propertyName));
        }

        $events = [];

        // TODO Calculate dimension space point set from dimension space point
        $dimensionSpacePointSet = new DimensionSpacePointSet([]);

        $events[] = new NodeAggregateWithNodeWasCreated(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier(),
            $command->getNodeTypeName(),
            $command->getDimensionSpacePoint(),
            $dimensionSpacePointSet,
            $command->getNodeIdentifier(),
            $command->getParentNodeIdentifier(),
            $command->getNodeName(),
            $propertyDefaultValuesAndTypes
        );

        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            $childNodeName = new NodeName($childNodeNameStr);
            $childNodeAggregateIdentifier = NodeAggregateIdentifier::forAutoCreatedChildNode($childNodeName, $command->getNodeAggregateIdentifier());
            // FIXME This auto-created child node identifier is random but should match the created child node persistence identifier of NodeData for the legacy layer
            $childNodeIdentifier = new NodeIdentifier();
            $childParentNodeIdentifier = $command->getNodeIdentifier();

            $events = array_merge($events, $this->nodeAggregateWithNodeWasCreatedFromCommand(new CreateNodeAggregateWithNode(
                $command->getContentStreamIdentifier(),
                $childNodeAggregateIdentifier,
                new NodeTypeName($childNodeType),
                $command->getDimensionSpacePoint(),
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
    public function handleCreateRootNode(CreateRootNode $command)
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
     * @param SetProperty $command
     */
    public function handleSetProperty(SetProperty $command)
    {
        $nodeType = $this->getNodeType($command->getNodeTypeName());
        $propertyType = $nodeType->getPropertyType($command->getPropertyName());

        $propertyValue = new PropertyValue($command->getValue(), $propertyType);

        $event = new PropertyWasSet(
            $command->getContentStreamIdentifier(),
            $command->getNodeIdentifier(),
            $command->getPropertyName(),
            $propertyValue
        );

        $this->eventPublisher->publish(ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()), $event);
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return \Neos\ContentRepository\Domain\Model\NodeType
     */
    private function getNodeType(NodeTypeName $nodeTypeName)
    {
        $this->validateNodeTypeName($nodeTypeName);

        $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeTypeName);
        return $nodeType;
    }

    /**
     * @param NodeTypeName $nodeTypeName
     */
    private function validateNodeTypeName(NodeTypeName $nodeTypeName)
    {
        if (!$this->nodeTypeManager->hasNodeType((string)$nodeTypeName)) {
            throw new \InvalidArgumentException('TODO: Node type ' . $nodeTypeName . ' not found.');
        }
    }
}
