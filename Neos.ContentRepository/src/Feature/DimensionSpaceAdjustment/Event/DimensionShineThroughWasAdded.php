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

namespace Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Add a Dimension Space Point Shine-Through;
 * basically making all content available not just in the source(original) DSP,
 * but also in the target-DimensionSpacePoint.
 *
 * NOTE: the Source Dimension Space Point must be a parent of the target Dimension Space Point.
 *
 * This is needed if "de" exists, and you want to create a "de_CH" specialization.
 *
 * NOTE: the target dimension space point must not contain any content.
 */
#[Flow\Proxy(false)]
final class DimensionShineThroughWasAdded implements DomainEventInterface, PublishableToOtherContentStreamsInterface
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private DimensionSpacePoint $source;

    private DimensionSpacePoint $target;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $source,
        DimensionSpacePoint $target
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->source = $source;
        $this->target = $target;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getSource(): DimensionSpacePoint
    {
        return $this->source;
    }

    public function getTarget(): DimensionSpacePoint
    {
        return $this->target;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->source,
            $this->target
        );
    }
}
