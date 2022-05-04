<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

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
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Feature\Migration\Transformation\GlobalTransformationInterface;

/**
 * Add a Dimension Space Point Shine-Through;
 * basically making all content available not just in the source(original) DSP,
 * but also in the target-DimensionSpacePoint.
 *
 * NOTE: the Source Dimension Space Point must be a parent of the target Dimension Space Point.
 */
class AddDimensionShineThrough implements GlobalTransformationInterface
{
    protected DimensionSpaceCommandHandler $dimensionSpacePointCommandHandler;

    protected ?DimensionSpacePoint $from;

    protected ?DimensionSpacePoint $to;

    public function __construct(DimensionSpaceCommandHandler $dimensionSpacePointCommandHandler)
    {
        $this->dimensionSpacePointCommandHandler = $dimensionSpacePointCommandHandler;
    }

    /**
     * @param array<string,string> $from
     */
    public function setFrom(array $from): void
    {
        $this->from = DimensionSpacePoint::fromArray($from);
    }

    /**
     * @param array<string,string> $to
     */
    public function setTo(array $to): void
    {
        $this->to = DimensionSpacePoint::fromArray($to);
    }

    public function execute(
        ContentStreamIdentifier $contentStreamForReading,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        if (is_null($this->from)) {
            throw new \RuntimeException('Cannot execute ' . self::class . ' without "from".', 1645387439);
        }
        if (is_null($this->to)) {
            throw new \RuntimeException('Cannot execute ' . self::class . ' without "to".', 1645387450);
        }

        return $this->dimensionSpacePointCommandHandler->handleAddDimensionShineThrough(
            new \Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough(
                $contentStreamForWriting,
                $this->from,
                $this->to
            )
        );
    }
}
