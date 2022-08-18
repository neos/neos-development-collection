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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValue;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

class AddNewPropertyTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        return new class (
            $settings['newPropertyName'],
            $settings['type'],
            $settings['serializedValue'],
            $contentRepository
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                /**
                 * Sets the name of the new property to be added.
                 */
                private readonly string $newPropertyName,
                private readonly string $type,
                /**
                 * Serialized Property value to be set.
                 */
                private readonly mixed $serializedValue,
                private readonly ContentRepository $contentRepository,
            ) {
            }

            public function execute(
                Node $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                ContentStreamIdentifier $contentStreamForWriting
            ): ?CommandResult {
                if (!$node->hasProperty($this->newPropertyName)) {
                    return $this->contentRepository->handle(
                        new SetSerializedNodeProperties(
                            $contentStreamForWriting,
                            $node->nodeAggregateIdentifier,
                            $node->originDimensionSpacePoint,
                            SerializedPropertyValues::fromArray([
                                $this->newPropertyName => new SerializedPropertyValue(
                                    $this->serializedValue,
                                    $this->type
                                )
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
