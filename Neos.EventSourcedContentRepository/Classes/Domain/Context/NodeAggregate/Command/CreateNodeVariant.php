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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * Create a variant of a node in a content stream
 *
 * Copy a node to another dimension space point respecting further variation mechanisms
 *
 * @Flow\Proxy(false)
 */
final class CreateNodeVariant implements \JsonSerializable
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    private OriginDimensionSpacePoint $sourceOrigin;

    private OriginDimensionSpacePoint $targetOrigin;

    private UserIdentifier $initiatingUserIdentifier;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceOrigin = $sourceOrigin;
        $this->targetOrigin = $targetOrigin;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            new OriginDimensionSpacePoint($array['sourceOrigin']),
            new OriginDimensionSpacePoint($array['targetOrigin']),
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getSourceOrigin(): OriginDimensionSpacePoint
    {
        return $this->sourceOrigin;
    }

    public function getTargetOrigin(): OriginDimensionSpacePoint
    {
        return $this->targetOrigin;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'sourceOrigin' => $this->sourceOrigin,
            'targetOrigin' => $this->targetOrigin,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }
}
