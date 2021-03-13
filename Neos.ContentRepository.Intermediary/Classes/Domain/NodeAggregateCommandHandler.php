<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Intermediary\Domain\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Intermediary\Domain\Command\PropertyValuesToWrite;
use Neos\ContentRepository\Intermediary\Domain\Command\SetNodeProperties;
use Neos\ContentRepository\Intermediary\Domain\Exception\PropertyCannotBeSet;
use Neos\ContentRepository\Intermediary\Domain\Property\PropertyConverter;
use Neos\ContentRepository\Intermediary\Domain\Property\PropertyType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler as LowLevelCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\Flow\Annotations as Flow;

/**
 * The intermediary's node aggregate command handler
 *
 * Responsible for higher level validation and serialization of properties
 *
 * @Flow\Scope("singleton")
 */
final class NodeAggregateCommandHandler
{
    protected LowLevelCommandHandler $lowLevelCommandHandler;

    protected PropertyConverter $propertyConverter;

    protected NodeTypeManager $nodeTypeManager;

    protected ContentGraphInterface $contentGraph;

    public function __construct(
        LowLevelCommandHandler $lowLevelCommandHandler,
        PropertyConverter $propertyConverter,
        NodeTypeManager $nodeTypeManager,
        ContentGraphInterface $contentGraph
    ) {
        $this->lowLevelCommandHandler = $lowLevelCommandHandler;
        $this->propertyConverter = $propertyConverter;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->contentGraph = $contentGraph;
    }

    public function handleCreateNodeAggregateWithNode(CreateNodeAggregateWithNode $command): CommandResult
    {
        $this->validateProperties($command->getInitialPropertyValues(), $command->getNodeTypeName());

        $lowLevelCommand = new CreateNodeAggregateWithNodeAndSerializedProperties(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier(),
            $command->getNodeTypeName(),
            $command->getOriginDimensionSpacePoint(),
            $command->getInitiatingUserIdentifier(),
            $command->getParentNodeAggregateIdentifier(),
            $command->getSucceedingSiblingNodeAggregateIdentifier(),
            $command->getNodeName(),
            $this->serializeProperties($command->getInitialPropertyValues(), $command->getNodeTypeName()),
            $command->getTetheredDescendantNodeAggregateIdentifiers()
        );

        return $this->lowLevelCommandHandler->handleCreateNodeAggregateWithNodeAndSerializedProperties($lowLevelCommand);
    }

    public function handleSetNodeProperties(SetNodeProperties $command): CommandResult
    {
        $nodeTypeName = $this->contentGraph->findNodeAggregateByIdentifier(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier()
        )->getNodeTypeName();

        $this->validateProperties($command->getPropertyValues(), $nodeTypeName);

        $lowLevelCommand = new SetSerializedNodeProperties(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier(),
            $command->getOriginDimensionSpacePoint(),
            $this->serializeProperties($command->getPropertyValues(), $nodeTypeName),
            $command->getInitiatingUserIdentifier()
        );

        return $this->lowLevelCommandHandler->handleSetSerializedNodeProperties($lowLevelCommand);
    }

    private function validateProperties(?PropertyValuesToWrite $propertyValues, NodeTypeName $nodeTypeName): void
    {
        if (!$propertyValues) {
            return;
        }

        $nodeType = $this->nodeTypeManager->getNodeType((string) $nodeTypeName);
        // initialize node type
        $nodeType->getOptions();
        foreach ($propertyValues->getValues() as $propertyName => $propertyValue) {
            $propertyType = PropertyType::fromNodeTypeDeclaration($nodeType->getPropertyType($propertyName));
            if (!$propertyType->isMatchedBy($propertyValue)) {
                throw PropertyCannotBeSet::becauseTheValueDoesNotMatchTheConfiguredType(
                    PropertyName::fromString($propertyName),
                    gettype($propertyValue),
                    $propertyType->getValue()
                );
            }
        }
    }

    private function serializeProperties(?PropertyValuesToWrite $propertyValues, NodeTypeName $nodeTypeName): ?SerializedPropertyValues
    {
        if (!$propertyValues) {
            return null;
        }
        $nodeType = $this->nodeTypeManager->getNodeType((string) $nodeTypeName);

        return $this->propertyConverter->serializePropertyValues($propertyValues, $nodeType);
    }
}
