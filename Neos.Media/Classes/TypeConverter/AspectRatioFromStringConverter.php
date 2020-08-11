<?php
declare(strict_types=1);

namespace Neos\Media\TypeConverter;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\ObjectConverter;
use Neos\Media\Domain\ValueObject\Configuration\AspectRatio;

/**
 * This converter transforms to \Neos\Media\Domain\ValueObject\Configuration\AspectRatio objects from string.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class AspectRatioFromStringConverter extends ObjectConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = AspectRatio::class;

    /**
     * Convert From
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return AspectRatio|object
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        try {
            return AspectRatio::fromString($source);
        } catch (\InvalidArgumentException $e) {
        }
        return null;
    }

    /**
     * Convert all properties in the source array
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        if (is_string($source)) {
            return [];
        }
        return parent::getSourceChildPropertiesToBeConverted($source);
    }
}
