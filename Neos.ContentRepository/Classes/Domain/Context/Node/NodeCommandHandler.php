<?php

namespace Neos\ContentRepository\Domain\Context\Node;

use Neos\ContentRepository\Domain\Context\Node\Event\ChildNodeWithVariantWasCreated;
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

    /**
     * @param CreateChildNodeWithVariant $command
     */
    public function handleCreateChildNodeWithVariant(CreateChildNodeWithVariant $command)
    {
        $streamName = 'editingsession:' . $command->getEditingSessionIdentifier();
        $events = $this->childNodeWithVariantWasCreatedFromCommand($command);

        $this->eventPublisher->publishMany($streamName, $events);
    }

    /**
     * create events for adding a node, including all subnodes (recursively)
     *
     * @param CreateChildNodeWithVariant $command
     * @return array<ChildNodeWithVariantWasCreated>
     */
    private function childNodeWithVariantWasCreatedFromCommand(CreateChildNodeWithVariant $command): array
    {
        if (!$this->nodeTypeManager->hasNodeType($command->getNodeTypeName())) {
            throw new \InvalidArgumentException('TODO: Node type ' . $command->getNodeTypeName() . ' not found.');
        }

        $nodeType = $this->nodeTypeManager->getNodeType($command->getNodeTypeName());

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
}