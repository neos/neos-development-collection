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

/** @codingStandardsIgnoreStart */

use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint as MoveDimensionSpacePointCommand;

/** @codingStandardsIgnoreEnd */

use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;

/**
 * move a dimension space point globally
 */
class MoveDimensionSpacePointTransformationFactory implements TransformationFactoryInterface
{
    public function __construct(private readonly DimensionSpaceCommandHandler $dimensionSpaceCommandHandler)
    {
    }

    public function build(array $settings): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface
    {
        $from = DimensionSpacePoint::fromArray($settings['from']);
        $to = DimensionSpacePoint::fromArray($settings['to']);
        return new class(
            $from,
            $to,
            $this->dimensionSpaceCommandHandler
        ) implements GlobalTransformationInterface {

            public function __construct(
                private readonly DimensionSpacePoint $from,
                private readonly DimensionSpacePoint $to,
                private readonly DimensionSpaceCommandHandler $dimensionSpaceCommandHandler,
            ) {}

            public function execute(
                ContentStreamIdentifier $contentStreamForReading,
                ContentStreamIdentifier $contentStreamForWriting
            ): CommandResult
            {
                return $this->dimensionSpaceCommandHandler->handleMoveDimensionSpacePoint(
                    new MoveDimensionSpacePointCommand(
                        $contentStreamForWriting,
                        $this->from,
                        $this->to
                    )
                );
            }
        };
    }
}
