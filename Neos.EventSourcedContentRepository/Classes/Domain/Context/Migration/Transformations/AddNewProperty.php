<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Migration\Transformations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Dto\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\Flow\Annotations as Flow;

/**
 * Change the node type.
 */
class AddNewProperty implements NodeBasedTransformationInterface
{

    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    /**
     * @var string
     */
    protected string $newPropertyName;

    /**
     * @var mixed
     */
    protected $value;

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    /**
     * Sets the name of the new property to be added.
     *
     * @param string $newPropertyName
     * @return void
     */
    public function setNewPropertyName(string $newPropertyName): void
    {
        $this->newPropertyName = $newPropertyName;
    }

    /**
     * Property value to be set.
     *
     * @param mixed $value
     * @return void
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function execute(NodeInterface $node, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        if (!$node->hasProperty($this->newPropertyName)) {
            return $this->nodeAggregateCommandHandler->handleSetNodeProperties(new SetNodeProperties(
                $contentStreamForWriting,
                $node->getNodeAggregateIdentifier(),
                $node->getOriginDimensionSpacePoint(),
                PropertyValuesToWrite::fromArray([
                    $this->newPropertyName => $this->value
                ])
            ));
        }

        return CommandResult::createEmpty();
    }
}
