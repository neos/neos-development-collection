<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command;

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
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Dto\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Traits\CommonSetNodePropertiesTrait;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * Set property values for a given node.
 *
 * The property values support arbitrary types (but must match the NodeType's property types -
 * this is validated in the command handler).
 *
 * Internally, this object is converted into a {@see SetSerializedNodeProperties} command, which is
 * then processed and stored.
 *
 * @Flow\Proxy(false)
 */
final class SetNodeProperties
{
    use CommonSetNodePropertiesTrait;

    private PropertyValuesToWrite $propertyValues;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        PropertyValuesToWrite $propertyValues,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->propertyValues = $propertyValues;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    /**
     * @return PropertyValuesToWrite
     * @internal
     */
    public function getPropertyValues(): PropertyValuesToWrite
    {
        return $this->propertyValues;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            new OriginDimensionSpacePoint($array['originDimensionSpacePoint']),
            PropertyValuesToWrite::fromArray($array['propertyValues']),
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }
}
