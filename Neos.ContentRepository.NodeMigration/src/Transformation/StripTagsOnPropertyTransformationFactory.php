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
use Neos\ContentRepository\Projection\ContentGraph\PropertyCollectionInterface;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValue;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

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
                ContentStreamIdentifier $contentStreamForWriting
            ): ?CommandResult {
                if ($node->hasProperty($this->propertyName)) {
                    /** @var PropertyCollectionInterface $properties */
                    $properties = $node->properties;
                    /** @var SerializedPropertyValue $serializedPropertyValue safe since Node::hasProperty */
                    $serializedPropertyValue = $properties->serialized()->getProperty($this->propertyName);
                    $propertyValue = $serializedPropertyValue->getValue();
                    if (!is_string($propertyValue)) {
                        throw new \Exception(
                            'StripTagsOnProperty can only be applied to properties of type string.',
                            1645391885
                        );
                    }
                    $newValue = strip_tags($propertyValue);
                    return $this->contentRepository->handle(
                        new SetSerializedNodeProperties(
                            $contentStreamForWriting,
                            $node->nodeAggregateIdentifier,
                            $node->originDimensionSpacePoint,
                            SerializedPropertyValues::fromArray([
                                $this->propertyName => new SerializedPropertyValue(
                                    $newValue,
                                    $serializedPropertyValue->getType()
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
