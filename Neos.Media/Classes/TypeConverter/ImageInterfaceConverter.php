<?php
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
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Utility\ObjectAccess;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;

/**
 * This converter transforms to \Neos\Media\Domain\Model\ImageInterface (Image or ImageVariant) objects.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageInterfaceConverter extends AssetInterfaceConverter
{
    /**
     * @Flow\Inject
     * @var ProcessingInstructionsConverter
     */
    protected $processingInstructionsConverter;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @var string
     */
    protected $targetType = ImageInterface::class;

    /**
     * @var integer
     */
    protected $priority = 2;

    /**
     * If creating a new asset from this converter this defines the default type as fallback.
     *
     * @var string
     */
    protected static $defaultNewAssetType = Image::class;

    /**
     * All properties in the source array except __identity are sub-properties.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        if (is_string($source)) {
            return [];
        }
        if (isset($source['adjustments'])) {
            unset($source['adjustments']);
        }
        if (isset($source['processingInstructions'])) {
            unset($source['processingInstructions']);
        }
        return parent::getSourceChildPropertiesToBeConverted($source);
    }

    /**
     * Converts and adds ImageAdjustments to the ImageVariant
     *
     * @param ImageInterface $asset
     * @param mixed $source
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return ImageInterface|NULL
     */
    protected function applyTypeSpecificHandling($asset, $source, array $convertedChildProperties, PropertyMappingConfigurationInterface $configuration)
    {
        if ($asset instanceof ImageVariant) {
            $adjustments = [];
            if (isset($source['adjustments'])) {
                foreach ($source['adjustments'] as $adjustmentType => $adjustmentOptions) {
                    if (isset($adjustmentOptions['__type'])) {
                        $adjustmentType = $adjustmentOptions['__type'];
                        unset($adjustmentOptions['__type']);
                    }
                    $identity = null;
                    if (isset($adjustmentOptions['__identity'])) {
                        $identity = $adjustmentOptions['__identity'];
                        unset($adjustmentOptions['__identity']);
                    }

                    $adjustment = $this->propertyMapper->convert($adjustmentOptions, $adjustmentType, $configuration);
                    if ($identity !== null) {
                        ObjectAccess::setProperty($adjustment, 'persistence_object_identifier', $identity, true);
                    }

                    $adjustments[] = $adjustment;
                }
            } elseif (isset($source['processingInstructions'])) {
                $adjustments = $this->processingInstructionsConverter->convertFrom($source['processingInstructions'], 'array');
            }

            if (count($adjustments) > 0) {
                $asset->addAdjustments($adjustments);
            }
        }

        return $asset;
    }
}
