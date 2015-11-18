<?php
namespace TYPO3\Media\TypeConverter;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\ImageVariant;

/**
 * This converter transforms to \TYPO3\Media\Domain\Model\ImageInterface (Image or ImageVariant) objects.
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
    protected $targetType = 'TYPO3\Media\Domain\Model\ImageInterface';

    /**
     * @var integer
     */
    protected $priority = 2;

    /**
     * If creating a new asset from this converter this defines the default type as fallback.
     *
     * @var string
     */
    protected static $defaultNewAssetType = 'TYPO3\Media\Domain\Model\Image';

    /**
     * All properties in the source array except __identity are sub-properties.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        if (is_string($source)) {
            return array();
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
                        \TYPO3\Flow\Reflection\ObjectAccess::setProperty($adjustment, 'persistence_object_identifier', $identity, true);
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
