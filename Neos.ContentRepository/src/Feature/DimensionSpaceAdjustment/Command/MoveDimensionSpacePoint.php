<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Move a dimension space point to a new location; basically moving all content to the new dimension space point.
 *
 * This is used to *rename* dimension space points, e.g. from "de" to "de_DE".
 *
 * NOTE: the target dimension space point must not contain any content.
 */
#[Flow\Proxy(false)]
final class MoveDimensionSpacePoint implements \JsonSerializable, RebasableToOtherContentStreamsInterface
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

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            DimensionSpacePoint::fromArray($array['source']),
            DimensionSpacePoint::fromArray($array['target'])
        );
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

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'source' => $this->source,
            'target' => $this->target,
        ];
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
