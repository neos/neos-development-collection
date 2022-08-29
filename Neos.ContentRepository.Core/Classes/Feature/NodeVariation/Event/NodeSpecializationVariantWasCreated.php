<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeVariation\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * A node specialization variant was created
 *
 * @api events are the persistence-API of the content repository
 */
final class NodeSpecializationVariantWasCreated implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateIdentifier
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly OriginDimensionSpacePoint $sourceOrigin,
        public readonly OriginDimensionSpacePoint $specializationOrigin,
        public readonly DimensionSpacePointSet $specializationCoverage,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->sourceOrigin,
            $this->specializationOrigin,
            $this->specializationCoverage,
            $this->initiatingUserIdentifier
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamIdentifier::fromString($values['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($values['nodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromArray($values['sourceOrigin']),
            OriginDimensionSpacePoint::fromArray($values['specializationOrigin']),
            DimensionSpacePointSet::fromArray($values['specializationCoverage']),
            UserIdentifier::fromString($values['initiatingUserIdentifier']),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'sourceOrigin' => $this->sourceOrigin,
            'specializationOrigin' => $this->specializationOrigin,
            'specializationCoverage' => $this->specializationCoverage,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }
}
