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

use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;

/**
 * move a dimension space point globally
 */
class MoveDimensionSpacePointTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,array<string,string>> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        $from = DimensionSpacePoint::fromArray($settings['from']);
        $to = DimensionSpacePoint::fromArray($settings['to']);
        return new class (
            $from,
            $to,
            $contentRepository
        ) implements GlobalTransformationInterface {
            public function __construct(
                private readonly DimensionSpacePoint $from,
                private readonly DimensionSpacePoint $to,
                private readonly ContentRepository $contentRepository,
            ) {
            }

            public function execute(
                ContentStreamId $contentStreamForReading,
                ContentStreamId $contentStreamForWriting
            ): CommandResult {
                return $this->contentRepository->handle(
                    new MoveDimensionSpacePoint(
                        $contentStreamForWriting,
                        $this->from,
                        $this->to
                    )
                );
            }
        };
    }
}
