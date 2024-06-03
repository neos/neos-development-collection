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

/**
 * Strip all tags on a given property
 */
class StripTagsOnPropertyTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,string> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        return new class (
            $settings['property'],
            $contentRepository
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                /**
                 * the name of the property to work on.
                 */
                private readonly string $propertyName,
                private readonly ContentRepository $contentRepository
            ) {
            }

            public function execute(
                Node $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                WorkspaceName $workspaceNameForWriting,
                ContentStreamId $contentStreamForWriting
            ): ?CommandResult {
                $serializedPropertyValue = $node->properties->serialized()->getProperty($this->propertyName);
                if ($serializedPropertyValue !== null) {
                    $propertyValue = $serializedPropertyValue->value;
                    if (!is_string($propertyValue)) {
                        throw new \Exception(
                            'StripTagsOnProperty can only be applied to properties of type string.',
                            1645391885
                        );
                    }
                    $newValue = strip_tags($propertyValue);
                    return $this->contentRepository->handle(
                        SetSerializedNodeProperties::create(
                            $workspaceNameForWriting,
                            $node->aggregateId,
                            $node->originDimensionSpacePoint,
                            SerializedPropertyValues::fromArray([
                                $this->propertyName => SerializedPropertyValue::create(
                                    $newValue,
                                    $serializedPropertyValue->type
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
