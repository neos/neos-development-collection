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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

class AddNewPropertyConverterAwareTransformationFactory implements PropertyConverterAwareTransformationFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository,
        PropertyConverter $propertyConverter,
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        return new class (
            $settings['newPropertyName'],
            $settings['type'],
            $settings['serializedValue'],
            $propertyConverter,
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
                private readonly PropertyConverter $propertyConverter,
            ) {
            }

            public function execute(
                Node $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                WorkspaceName $workspaceNameForWriting
            ): TransformationStep {
                if ($this->serializedValue === null) {
                    // we don't need to unset a non-existing property
                    return TransformationStep::createEmpty();
                }
                $deserializedPropertyValue = $this->propertyConverter->deserializePropertyValue(SerializedPropertyValue::create($this->serializedValue, $this->type)); // @phpstan-ignore neos.cr.internal

                if (!$node->hasProperty($this->newPropertyName)) {
                    return TransformationStep::fromCommand(
                        SetNodeProperties::create(
                            $workspaceNameForWriting,
                            $node->aggregateId,
                            $node->originDimensionSpacePoint,
                            PropertyValuesToWrite::fromArray([
                                $this->newPropertyName => $deserializedPropertyValue,
                            ])
                        )
                    );
                }

                return TransformationStep::createEmpty();
            }
        };
    }
}
