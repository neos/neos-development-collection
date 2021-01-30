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
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Dto\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;

/**
 * Remove the property
 */
class RemoveProperty implements NodeBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    /**
     * @var string
     */
    protected string $propertyName = '';

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    /**
     * Sets the name of the property to be removed.
     *
     * @param string $propertyName
     * @return void
     */
    public function setProperty(string $propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * If the given node has property this transformation should work on, this
     * returns true.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return $node->hasProperty($this->propertyName);
    }

    /**
     * Remove the property from the given node.
     *
     * @param NodeData $node
     * @return void
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    public function exescute(NodeData $node)
    {
        $node->removeProperty($this->propertyName);
    }

    public function execute(NodeInterface $node, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        if ($node->hasProperty($this->propertyName)) {
            return $this->nodeAggregateCommandHandler->handleSetNodeProperties(new SetNodeProperties(
                $contentStreamForWriting,
                $node->getNodeAggregateIdentifier(),
                $node->getOriginDimensionSpacePoint(),
                PropertyValuesToWrite::fromArray([
                    $this->propertyName => null
                ])
            ));
        }

        return CommandResult::createEmpty();
    }
}
