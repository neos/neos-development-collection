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
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Dto\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;

/**
 * Remove the property
 */
class RenameProperty implements NodeBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    /**
     * Property name to change
     */
    protected string $oldPropertyName;

    /**
     * New name of property
     */
    protected string $newPropertyName;

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    /**
     * Sets the name of the property to change.
     * @param string $oldPropertyName
     */
    public function setFrom(string $oldPropertyName): void
    {
        $this->oldPropertyName = $oldPropertyName;
    }

    /**
     * Sets the new name for the property to change.
     * @param string $newPropertyName
     */
    public function setTo(string $newPropertyName): void
    {
        $this->newPropertyName = $newPropertyName;
    }

    /**
     * Remove the property from the given node.
     *
     * @param NodeInterface $node
     * @param ContentStreamIdentifier $contentStreamForWriting
     * @return CommandResult
     */
    public function execute(NodeInterface $node, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        if ($node->hasProperty($this->oldPropertyName)) {
            return $this->nodeAggregateCommandHandler->handleSetNodeProperties(new SetNodeProperties(
                $contentStreamForWriting,
                $node->getNodeAggregateIdentifier(),
                $node->getOriginDimensionSpacePoint(),
                PropertyValuesToWrite::fromArray([
                    $this->newPropertyName => $node->getProperty($this->oldPropertyName),
                    $this->oldPropertyName => null
                ])
            ));
        }

        return CommandResult::createEmpty();
    }
}
