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
use Neos\ContentRepository\Intermediary\Domain\Property\PropertyConverter;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler as LowLevelCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\Flow\Annotations as Flow;

/**
 * The intermediary's node aggregate command handler
 *
 * Responsible for higher level validation and serialization of properties
 */
final class NodeAggregateCommandHandler
{
    /**
     * @Flow\Inject
     * @var LowLevelCommandHandler
     */
    protected $lowLevelCommandHandler;

    /**
     * @Flow\Inject
     * @var PropertyConverter
     */
    protected $propertyConverter;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

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
        // @todo implement me
    }

    private function serializeProperties(?PropertyValuesToWrite $propertyValues, NodeTypeName $nodeTypeName): ?SerializedPropertyValues
    {
        if (!$propertyValues) {
            return null;
        }
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);

        return $this->propertyConverter->serializePropertyValues($propertyValues, $nodeType);
    }
}
