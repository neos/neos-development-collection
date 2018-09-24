<?php
namespace Neos\ContentRepository\DimensionSpace\TypeConverter;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;


class DimensionSpacePointSetConverter extends AbstractTypeConverter
{
    protected $sourceTypes = ['array'];
    protected $targetType = DimensionSpacePointSet::class;
    protected $priority = 10;

    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        return new DimensionSpacePointSet($convertedChildProperties);
    }

    public function getSourceChildPropertiesToBeConverted($source)
    {
        if (is_array($source)) {
            return $source;
        }
        return [];
    }

    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration)
    {
        return DimensionSpacePoint::class;
    }
}
