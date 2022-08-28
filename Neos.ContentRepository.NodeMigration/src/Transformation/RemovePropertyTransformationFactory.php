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
use Neos\ContentRepository\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * Remove the property from nodes
 */
class RemovePropertyTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,string> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        $propertyName = $settings['property'];
        return new class (
            $propertyName,
            $contentRepository
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                /**
                 * the name of the property to be removed.
                 */
                private readonly string $propertyName,
                private readonly ContentRepository $contentRepository
            ) {
            }
            public function execute(
                Node $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                ContentStreamIdentifier $contentStreamForWriting
            ): ?CommandResult {
                if ($node->hasProperty($this->propertyName)) {
                    return $this->contentRepository->handle(
                        new SetSerializedNodeProperties(
                            $contentStreamForWriting,
                            $node->nodeAggregateIdentifier,
                            $node->originDimensionSpacePoint,
                            SerializedPropertyValues::fromArray([
                                $this->propertyName => null
                            ]),
                            UserIdentifier::forSystemUser()
                        )
                    );
                }

                return null;
            }
        };
    }
}
