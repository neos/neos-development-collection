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

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\CommandHandler\CommandResult;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;

/**
 * Add a Dimension Space Point Shine-Through;
 * basically making all content available not just in the source(original) DSP,
 * but also in the target-DimensionSpacePoint.
 *
 * NOTE: the Source Dimension Space Point must be a parent of the target Dimension Space Point.
 */
class AddDimensionShineThroughTransformationFactory implements TransformationFactoryInterface
{
    public function __construct(private readonly ContentRepository $contentRepository)
    {
    }

    /**
     * @param array<string,array<string,string>> $settings
     */
    public function build(
        array $settings
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        return new class (
            DimensionSpacePoint::fromArray($settings['from']),
            DimensionSpacePoint::fromArray($settings['to']),
            $this->contentRepository
        ) implements GlobalTransformationInterface {
            public function __construct(
                private readonly DimensionSpacePoint $from,
                private readonly DimensionSpacePoint $to,
                private readonly ContentRepository $contentRepository,
            ) {
            }

            public function execute(
                ContentStreamIdentifier $contentStreamForReading,
                ContentStreamIdentifier $contentStreamForWriting
            ): CommandResult {
                return $this->contentRepository->handle(
                    new AddDimensionShineThrough(
                        $contentStreamForWriting,
                        $this->from,
                        $this->to
                    )
                );
            }
        };
    }
}
