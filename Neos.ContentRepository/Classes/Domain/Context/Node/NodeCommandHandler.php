<?php

namespace Neos\ContentRepository\Domain\Context\Node;

use Neos\ContentRepository\Domain\Context\Node\Command\SetProperty;
use Neos\ContentRepository\Domain\Context\Node\Event\ChildNodeWithVariantWasCreated;
use Neos\ContentRepository\Domain\ValueObject\EditingSessionIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
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

    private static function getStreamNameForEditingSession(EditingSessionIdentifier $editingSessionIdentifier)
    {
        return 'editingsession:' . $editingSessionIdentifier;
    }

    /**
     * @param CreateChildNodeWithVariant $command
     */
    public function handleCreateChildNodeWithVariant(CreateChildNodeWithVariant $command)
    {
        $events = $this->childNodeWithVariantWasCreatedFromCommand($command);

        $this->eventPublisher->publishMany(self::getStreamNameForEditingSession($command->getEditingSessionIdentifier()), $events);
    }

    /**
     * create events for adding a node, including all subnodes (recursively)
     *
     * @param CreateChildNodeWithVariant $command
     * @return array<ChildNodeWithVariantWasCreated>
     */
    private function childNodeWithVariantWasCreatedFromCommand(CreateChildNodeWithVariant $command): array
    {
        if (empty($command->getNodeTypeName())) {
            throw new \InvalidArgumentException('TODO: Node type may not be null');
        }
        if (!$this->nodeTypeManager->hasNodeType((string)$command->getNodeTypeName())) {
            throw new \InvalidArgumentException('TODO: Node type ' . $command->getNodeTypeName() . ' not found.');
        }

        $nodeType = $this->nodeTypeManager->getNodeType((string)$command->getNodeTypeName());

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
                $command->getEditingSessionIdentifier(),
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

        // TODO continue

        //$this->eventPublisher->publish(self::getStreamNameForEditingSession($command->getEditingSessionIdentifier()), $event);
    }
}