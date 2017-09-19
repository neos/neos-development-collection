<?php

namespace Neos\ContentRepository\Domain\Context\Node;

use Neos\ContentRepository\Domain\Context\Node\Command\SetProperty;
use Neos\ContentRepository\Domain\Context\Node\Event\ChildNodeWithVariantWasCreated;
use Neos\ContentRepository\Domain\Context\Node\Event\PropertyWasSet;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateChildNodeWithVariant;

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

    private static function getStreamNameForContentStream(ContentStreamIdentifier $contentStreamIdentifier)
    {
        return 'contentstream:' . $contentStreamIdentifier;
    }

    /**
     * @param CreateChildNodeWithVariant $command
     */
    public function handleCreateChildNodeWithVariant(CreateChildNodeWithVariant $command)
    {
        $events = $this->childNodeWithVariantWasCreatedFromCommand($command);

        $this->eventPublisher->publishMany(self::getStreamNameForContentStream($command->getContentStreamIdentifier()), $events);
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
            $command->getParentNodeIdentifier(),
            $command->getNodeIdentifier(),
            $command->getNodeName(),
            $command->getNodeTypeName(),
            $command->getDimensionValues(),
            $propertyDefaultValuesAndTypes
        );

        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            $childNodeName = new NodeName($childNodeNameStr);
            $childNodeIdentifier = NodeIdentifier::forAutoCreatedChildNode($childNodeName,
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

    public function handleSetProperty(SetProperty $command)
    {
        $nodeType = $this->getNodeType($command->getNodeTypeName());
        $propertyType = $nodeType->getPropertyType($command->getPropertyName());

        $propertyValue = new PropertyValue($command->getValue(), $propertyType);

        $event = new PropertyWasSet(
            $command->getNodeIdentifier(),
            $command->getPropertyName(),
            $propertyValue
        );

        $this->eventPublisher->publish(self::getStreamNameForContentStream($command->getContentStreamIdentifier()), $event);
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return \Neos\ContentRepository\Domain\Model\NodeType
     */
    private function getNodeType(NodeTypeName $nodeTypeName)
    {
        if (!$this->nodeTypeManager->hasNodeType((string)$nodeTypeName)) {
            throw new \InvalidArgumentException('TODO: Node type ' . $nodeTypeName . ' not found.');
        }

        $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeTypeName);
        return $nodeType;
    }
}
