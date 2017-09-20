<?php

namespace Neos\ContentRepository\Domain\Context\Node;

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\ContentRepository\Domain\Context\Importing\Command\FinalizeImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Command\StartImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasFinalized;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasStarted;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateChildNodeWithVariant;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateRootNode;
use Neos\ContentRepository\Domain\Context\Importing\Command\ImportNode;
use Neos\ContentRepository\Domain\Context\Importing\Event\NodeWasImported;
use Neos\ContentRepository\Domain\Context\Node\Command\SetProperty;
use Neos\ContentRepository\Domain\Context\Node\Event\ChildNodeWithVariantWasCreated;
use Neos\ContentRepository\Domain\Context\Node\Event\PropertyWasSet;
use Neos\ContentRepository\Domain\Context\Node\Event\RootNodeWasCreated;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
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
     * @param CreateChildNodeWithVariant $command
     */
    public function handleCreateChildNodeWithVariant(CreateChildNodeWithVariant $command)
    {
        $events = $this->childNodeWithVariantWasCreatedFromCommand($command);
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
     * Create events for adding a node, including all auto-created child nodes (recursively)
     *
     * @param CreateChildNodeWithVariant $command
     * @return array <ChildNodeWithVariantWasCreated>
     * @throws NodeTypeNotFoundException
     */
    private function childNodeWithVariantWasCreatedFromCommand(CreateChildNodeWithVariant $command): array
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

        $events[] = new ChildNodeWithVariantWasCreated(
            $command->getContentStreamIdentifier(),
            $command->getParentNodeIdentifier(),
            $command->getNodeIdentifier(),
            $command->getNodeName(),
            $command->getNodeTypeName(),
            $command->getDimensionValues(),
            $propertyDefaultValuesAndTypes
        );

        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            $childNodeName = new NodeName($childNodeNameStr);
            $childNodeIdentifier = NodeAggregateIdentifier::forAutoCreatedChildNode($childNodeName,
                $command->getNodeIdentifier());

            $events = array_merge($events, $this->childNodeWithVariantWasCreatedFromCommand(new CreateChildNodeWithVariant(
                $command->getContentStreamIdentifier(),
                $command->getNodeIdentifier(),
                $childNodeIdentifier,
                $childNodeName,
                new NodeTypeName($childNodeType),
                $command->getDimensionValues()
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
