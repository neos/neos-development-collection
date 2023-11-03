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
 * @api commands are the write-API of the ContentRepository
 */
final readonly class AddDimensionShineThrough implements
    CommandInterface,
    \JsonSerializable
{
    /**
     * @param WorkspaceName $workspaceName The name of the workspace to perform the operation in
     * @param DimensionSpacePoint $source source dimension space point
     * @param DimensionSpacePoint $target target dimension space point
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public DimensionSpacePoint $source,
        public DimensionSpacePoint $target
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The name of the workspace to perform the operation in
     * @param DimensionSpacePoint $source source dimension space point
     * @param DimensionSpacePoint $target target dimension space point
     */
    public static function create(WorkspaceName $workspaceName, DimensionSpacePoint $source, DimensionSpacePoint $target): self
    {
        return new self($workspaceName, $source, $target);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
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
            'workspaceName' => $this->workspaceName,
            'source' => $this->source,
            'target' => $this->target,
        ];
    }
}
