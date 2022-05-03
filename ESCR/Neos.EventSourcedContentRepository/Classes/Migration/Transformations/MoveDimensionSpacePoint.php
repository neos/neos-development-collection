<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Migration\Transformations;

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
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
/** @codingStandardsIgnoreStart */
use Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace\Command\MoveDimensionSpacePoint as MoveDimensionSpacePointCommand;
/** @codingStandardsIgnoreEnd */
use Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace\DimensionSpaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\CommandResult;

/**
 * move a dimension space point globally
 */
class MoveDimensionSpacePoint implements GlobalTransformationInterface
{
    protected DimensionSpaceCommandHandler $dimensionSpacePointCommandHandler;

    protected DimensionSpacePoint $from;

    protected DimensionSpacePoint $to;

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
        return $this->dimensionSpacePointCommandHandler->handleMoveDimensionSpacePoint(
            new MoveDimensionSpacePointCommand(
                $contentStreamForWriting,
                $this->from,
                $this->to
            )
        );
    }
}
