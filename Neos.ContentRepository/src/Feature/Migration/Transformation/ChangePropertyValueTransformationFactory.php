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
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Projection\Content\PropertyCollectionInterface;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValue;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * Change the value of a given property.
 *
 * This can apply two transformations:
 *
 * If newValue is set, the value will be set to this, with any occurrences of the currentValuePlaceholder replaced with
 * the current value of the property.
 *
 * If search and replace are given, that replacement will be done on the value (after applying the newValue if set).
 */
class ChangePropertyValueTransformationFactory implements TransformationFactoryInterface
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
        $newSerializedValue = '{current}';
        if (isset($settings['newSerializedValue'])) {
            $newSerializedValue = $settings['newSerializedValue'];
        }

        $search = '';
        if (isset($settings['search'])) {
            $search = $settings['search'];
        }

        $replace = '';
        if (isset($settings['replace'])) {
            $replace = $settings['replace'];
        }

        $currentValuePlaceholder = '{current}';
        if (isset($settings['currentValuePlaceholder'])) {
            $currentValuePlaceholder = $settings['currentValuePlaceholder'];
        }

        return new class (
            $settings['property'],
            $newSerializedValue,
            $search,
            $replace,
            $currentValuePlaceholder,
            $this->nodeAggregateCommandHandler
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                /**
                 * The name of the property to change.
                 */
                private readonly string $propertyName,
                /**
                 * New property value to be set.
                 *
                 * The value of the option "currentValuePlaceholder" (defaults to "{current}") will be
                 * used to include the current property value into the new value.
                 */
                private readonly string $newSerializedValue,
                /**
                 * Search string to replace in current property value.
                 */
                private readonly string $search,
                /**
                 * Replacement for the search string
                 */
                private readonly string $replace,
                /**
                 * The value of this option (defaults to "{current}") will be used to include the
                 * current property value into the new value.
                 */
                private readonly string $currentValuePlaceholder,
                private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler
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
                    $currentProperty = $properties->serialized()->getProperty($this->propertyName);
                    /** @var SerializedPropertyValue $currentProperty safe since NodeInterface::hasProperty */
                    $value = $currentProperty->getValue();
                    if (!is_string($value) && !is_array($value)) {
                        throw new \Exception(
                            'ChangePropertyValue can only be executed on properties'
                                . ' with serialized type string or array.',
                            1645391685
                        );
                    }
                    $newValueWithReplacedCurrentValue = str_replace(
                        $this->currentValuePlaceholder,
                        $value,
                        $this->newSerializedValue
                    );
                    $newValueWithReplacedSearch = str_replace(
                        $this->search,
                        $this->replace,
                        $newValueWithReplacedCurrentValue
                    );

                    return $this->nodeAggregateCommandHandler->handleSetSerializedNodeProperties(
                        new SetSerializedNodeProperties(
                            $contentStreamForWriting,
                            $node->getNodeAggregateIdentifier(),
                            $node->getOriginDimensionSpacePoint(),
                            SerializedPropertyValues::fromArray([
                                $this->propertyName => new SerializedPropertyValue(
                                    $newValueWithReplacedSearch,
                                    $currentProperty->getType()
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
