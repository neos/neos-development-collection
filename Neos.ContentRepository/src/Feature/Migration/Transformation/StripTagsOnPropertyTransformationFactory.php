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

namespace Neos\ContentRepository\Feature\Migration\Transformation;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Projection\ContentGraph\PropertyCollectionInterface;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValue;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * Strip all tags on a given property
 */
class StripTagsOnPropertyTransformationFactory implements TransformationFactoryInterface
{
    public function __construct(private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
    }

    /**
     * @param array<string,string> $settings
     */
    public function build(
        array $settings
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        return new class (
            $settings['property'],
            $this->nodeAggregateCommandHandler
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                /**
                 * the name of the property to work on.
                 */
                private readonly string $propertyName,
                private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler,
            ) {
            }

            public function execute(
                NodeInterface $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                ContentStreamIdentifier $contentStreamForWriting
            ): CommandResult {
                if ($node->hasProperty($this->propertyName)) {
                    /** @var PropertyCollectionInterface $properties */
                    $properties = $node->getProperties();
                    /** @var SerializedPropertyValue $serializedPropertyValue safe since NodeInterface::hasProperty */
                    $serializedPropertyValue = $properties->serialized()->getProperty($this->propertyName);
                    $propertyValue = $serializedPropertyValue->getValue();
                    if (!is_string($propertyValue)) {
                        throw new \Exception(
                            'StripTagsOnProperty can only be applied to properties of type string.',
                            1645391885
                        );
                    }
                    $newValue = strip_tags($propertyValue);
                    return $this->nodeAggregateCommandHandler->handleSetSerializedNodeProperties(
                        new SetSerializedNodeProperties(
                            $contentStreamForWriting,
                            $node->getNodeAggregateIdentifier(),
                            $node->getOriginDimensionSpacePoint(),
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

                return CommandResult::createEmpty();
            }
        };
    }
}
