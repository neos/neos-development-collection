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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

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
                WorkspaceName $workspaceNameForWriting,
                ContentStreamId $contentStreamForWriting
            ): ?CommandResult {
                if ($this->serializedValue === null) {
                    // we don't need to unset a non-existing property
                    return null;
                }

                if (!$node->hasProperty($this->newPropertyName)) {
                    return $this->contentRepository->handle(
                        SetSerializedNodeProperties::create(
                            $workspaceNameForWriting,
                            $node->aggregateId,
                            $node->originDimensionSpacePoint,
                            SerializedPropertyValues::fromArray([
                                $this->newPropertyName => SerializedPropertyValue::create(
                                    $this->serializedValue,
                                    $this->type
                                )
                            ]),
                            PropertyNames::createEmpty()
                        )
                    );
                }

                return null;
            }
        };
    }
}
