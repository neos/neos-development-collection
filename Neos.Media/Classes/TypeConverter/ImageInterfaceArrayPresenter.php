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
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Utility\ObjectAccess;
use Neos\Utility\TypeHandling;
use Neos\Flow\Validation\Error;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;

/**
 * This converter transforms \Neos\Media\Domain\Model\ImageInterface (Image or ImageVariant) objects to array representations.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageInterfaceArrayPresenter extends AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = [ImageInterface::class];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var integer
     */
    protected $priority = 0;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * If $source has an identity, we have a persisted Image, and therefore
     * this type converter should withdraw and let the PersistedObjectConverter kick in.
     *
     * @param mixed $source The source for the to-build Image
     * @param string $targetType Should always be 'string'
     * @return boolean
     */
    public function canConvertFrom($source, $targetType)
    {
        return true;
    }

    /**
     * Convert all properties in the source array
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return [];
    }

    /**
     * Convert an object from \Neos\Media\Domain\Model\ImageInterface to a json representation
     *
     * @param ImageInterface $source
     * @param string $targetType must be 'string'
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string|Error The converted Image, a Validation Error or NULL
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $data = [
            '__identity' => $this->persistenceManager->getIdentifierByObject($source),
            '__type' => TypeHandling::getTypeForValue($source)
        ];

        if ($source instanceof ImageVariant) {
            $data['originalAsset'] = [
                '__identity' => $this->persistenceManager->getIdentifierByObject($source->getOriginalAsset()),
            ];

            $adjustments = [];
            foreach ($source->getAdjustments() as $adjustment) {
                $index = TypeHandling::getTypeForValue($adjustment);
                $adjustments[$index] = [];
                foreach (ObjectAccess::getGettableProperties($adjustment) as $propertyName => $propertyValue) {
                    $adjustments[$index][$propertyName] = $propertyValue;
                }
            }
            $data['adjustments'] = $adjustments;
        }

        return $data;
    }
}
