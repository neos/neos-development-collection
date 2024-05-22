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
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

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
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class DimensionShineThroughWasAdded implements EventInterface, PublishableToWorkspaceInterface
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public DimensionSpacePoint $source,
        public DimensionSpacePoint $target
    ) {
    }

    public function withWorkspaceNameAndContentStreamId(WorkspaceName $targetWorkspaceName, ContentStreamId $contentStreamId): self
    {
        return new self(
            $targetWorkspaceName,
            $contentStreamId,
            $this->source,
            $this->target
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['contentStreamId']),
            DimensionSpacePoint::fromArray($values['source']),
            DimensionSpacePoint::fromArray($values['target'])
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
