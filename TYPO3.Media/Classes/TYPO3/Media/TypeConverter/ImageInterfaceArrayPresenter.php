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
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Media\Domain\Model\ImageInterface;

/**
 * This converter transforms \TYPO3\Media\Domain\Model\ImageInterface (Image or ImageVariant) objects to array representations.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageInterfaceArrayPresenter extends AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = array('TYPO3\Media\Domain\Model\ImageInterface');

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
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
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
        return array();
    }

    /**
     * Convert an object from \TYPO3\Media\Domain\Model\ImageInterface to a json representation
     *
     * @param ImageInterface $source
     * @param string $targetType must be 'string'
     * @param array $convertedChildProperties
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return string|\TYPO3\Flow\Validation\Error The converted Image, a Validation Error or NULL
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        $data = array(
            '__identity' => $this->persistenceManager->getIdentifierByObject($source),
            '__type' => TypeHandling::getTypeForValue($source)
        );

        if ($source instanceof \TYPO3\Media\Domain\Model\ImageVariant) {
            $data['originalAsset'] = [
                '__identity' => $this->persistenceManager->getIdentifierByObject($source->getOriginalAsset()),
            ];

            $adjustments = array();
            foreach ($source->getAdjustments() as $adjustment) {
                $index = TypeHandling::getTypeForValue($adjustment);
                $adjustments[$index] = array();
                foreach (\TYPO3\Flow\Reflection\ObjectAccess::getGettableProperties($adjustment) as $propertyName => $propertyValue) {
                    $adjustments[$index][$propertyName] = $propertyValue;
                }
            }
            $data['adjustments'] = $adjustments;
        }

        return $data;
    }
}
