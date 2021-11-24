<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Migration\Transformations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * Strip all tags on a given property
 */
class StripTagsOnProperty implements NodeBasedTransformationInterface
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
     * Sets the name of the property to work on.
     *
     * @param string $propertyName
     * @return void
     */
    public function setProperty(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    public function execute(NodeInterface $node, DimensionSpacePointSet $coveredDimensionSpacePoints, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        if ($node->hasProperty($this->propertyName)) {
            $newValue = strip_tags($node->getProperties()->serialized()->getProperty($this->propertyName)->getValue());
            return $this->nodeAggregateCommandHandler->handleSetSerializedNodeProperties(new SetSerializedNodeProperties(
                $contentStreamForWriting,
                $node->getNodeAggregateIdentifier(),
                $node->getOriginDimensionSpacePoint(),
                SerializedPropertyValues::fromArray([
                    $this->propertyName => new SerializedPropertyValue($newValue, $node->getProperties()->serialized()->getProperty($this->propertyName)->getType())
                ]),
                UserIdentifier::forSystemUser()
            ));
        }

        return CommandResult::createEmpty();
    }
}
