<?php

namespace Neos\ContentRepository\Domain\Context\Node;

use Neos\ContentRepository\Domain\Context\Node\Event\ChildNodeWithVariantWasCreated;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateChildNodeWithVariant;

class NodeCommandHandler
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

    public function handleCreateChildNodeWithVariant(CreateChildNodeWithVariant $command)
    {
        $streamName = 'editingsession:' . $command->getEditingSessionIdentifier();
        $events = [];
        $events[] = $this->childNodeWithVariantWasCreatedFromCommand($command);

        $nodeType = $this->resolveNodeTypeForNodeTypeName($command->getNodeTypeName());
        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            $childNodeName = new NodeName($childNodeNameStr);
            $childNodeIdentifier = NodeIdentifier::forAutoCreatedChildNode($childNodeName,
                $command->getNodeIdentifier());

            $events[] = $this->childNodeWithVariantWasCreatedFromCommand(new CreateChildNodeWithVariant(
                $command->getEditingSessionIdentifier(),
                $command->getNodeIdentifier(),
                $childNodeIdentifier,
                $childNodeName,
                new NodeTypeName($childNodeType),
                $command->getDimensionValues()
            ));
        }

        $this->eventPublisher->publishMany($streamName, $events);
    }

    private function childNodeWithVariantWasCreatedFromCommand(CreateChildNodeWithVariant $command): ChildNodeWithVariantWasCreated
    {
        $nodeType = $this->resolveNodeTypeForNodeTypeName($command->getNodeTypeName());

        $propertyDefaultValuesAndTypes = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
            $propertyDefaultValuesAndTypes[$propertyName] = new PropertyValue($propertyValue,
                $nodeType->getPropertyType($propertyName));
        }

        return new ChildNodeWithVariantWasCreated(
            $command->getParentNodeIdentifier(),
            $command->getNodeIdentifier(),
            $command->getNodeName(),
            $command->getNodeTypeName(),
            $command->getDimensionValues(),
            $propertyDefaultValuesAndTypes
        );
    }


    private function resolveNodeTypeForNodeTypeName(string $nodeTypeName)
    {
        if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            throw new \InvalidArgumentException('TODO: Node type ' . $nodeTypeName . ' not found.');
        }

        return $this->nodeTypeManager->getNodeType($nodeTypeName);
    }


}