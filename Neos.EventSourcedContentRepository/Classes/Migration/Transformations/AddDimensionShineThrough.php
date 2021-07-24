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
use Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace\DimensionSpaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;

/**
 * Add a Dimension Space Point Shine-Through; basically making all content available not just in the source(original) DSP, but also
 * in the target-DimensionSpacePoint.
 *
 * NOTE: the Source Dimension Space Point must be a parent of the target Dimension Space Point.
 */
class AddDimensionShineThrough implements GlobalTransformationInterface
{

    protected DimensionSpaceCommandHandler $dimensionSpacePointCommandHandler;

    /**
     * @var DimensionSpacePoint
     */
    protected $from;

    /**
     * @var DimensionSpacePoint
     */
    protected $to;

    public function __construct(DimensionSpaceCommandHandler $dimensionSpacePointCommandHandler)
    {
        $this->dimensionSpacePointCommandHandler = $dimensionSpacePointCommandHandler;
    }

    /**
     * @param array $from
     */
    public function setFrom(array $from): void
    {
        $this->from = DimensionSpacePoint::fromArray($from);
    }

    /**
     * @param array $to
     */
    public function setTo(array $to): void
    {
        $this->to = DimensionSpacePoint::fromArray($to);
    }

    public function execute(ContentStreamIdentifier $contentStreamForReading, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        return $this->dimensionSpacePointCommandHandler->handleAddDimensionShineThrough(new \Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace\Command\AddDimensionShineThrough(
            $contentStreamForWriting,
            $this->from,
            $this->to
        ));
    }
}
