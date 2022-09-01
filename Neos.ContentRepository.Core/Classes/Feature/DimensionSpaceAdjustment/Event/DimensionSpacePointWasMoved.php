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

namespace Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * Moved a dimension space point to a new location; basically moving all content to the new dimension space point.
 *
 * This is used to *rename* dimension space points, e.g. from "de" to "de_DE".
 *
 * NOTE: the target dimension space point must not contain any content.
 *
 * @api events are the persistence-API of the content repository
 */
final class DimensionSpacePointWasMoved implements EventInterface, PublishableToOtherContentStreamsInterface
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly DimensionSpacePoint $source,
        public readonly DimensionSpacePoint $target
    ) {
    }

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetContentStreamId,
            $this->source,
            $this->target
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            DimensionSpacePoint::fromArray($values['source']),
            DimensionSpacePoint::fromArray($values['target'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'source' => $this->source,
            'target' => $this->target,
        ];
    }
}
