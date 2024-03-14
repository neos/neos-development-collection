<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Move a dimension space point to a new location; basically moving all content to the new dimension space point.
 *
 * This is used to *rename* dimension space points, e.g. from "de" to "de_DE".
 *
 * NOTE: the target dimension space point must not contain any content.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class MoveDimensionSpacePoint implements
    \JsonSerializable,
    CommandInterface,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param ContentStreamId $contentStreamId The id of the content stream to perform the operation in.
     *       This content stream is specifically created for this operation and thus not prone to conflicts,
     *       thus we don't need the workspace name
     * @param DimensionSpacePoint $source source dimension space point
     * @param DimensionSpacePoint $target target dimension space point
     */
    private function __construct(
        public ContentStreamId $contentStreamId,
        public DimensionSpacePoint $source,
        public DimensionSpacePoint $target
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

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
        ContentStreamId $targetContentStreamId
    ): self {
        return new self(
            $targetContentStreamId,
            $this->source,
            $this->target
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
