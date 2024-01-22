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

namespace Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;

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
 * @api commands are the write-API of the ContentRepository
 */
final class AddDimensionShineThrough implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface
{
    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to perform the operation in
     * @param DimensionSpacePoint $source source dimension space point
     * @param DimensionSpacePoint $target target dimension space point
     */
    private function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly DimensionSpacePoint $source,
        public readonly DimensionSpacePoint $target
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to perform the operation in
     * @param DimensionSpacePoint $source source dimension space point
     * @param DimensionSpacePoint $target target dimension space point
     */
    public static function create(ContentStreamId $contentStreamId, DimensionSpacePoint $source, DimensionSpacePoint $target): self
    {
        return new self($contentStreamId, $source, $target);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
            DimensionSpacePoint::fromArray($array['source']),
            DimensionSpacePoint::fromArray($array['target'])
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'source' => $this->source,
            'target' => $this->target,
        ];
    }

    public function createCopyForContentStream(ContentStreamId $target): self
    {
        return new self(
            $target,
            $this->source,
            $this->target
        );
    }
}
