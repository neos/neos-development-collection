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
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;

/**
 * This converter transforms TYPO3.Media AssetInterface instances to arrays.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ArrayConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $sourceTypes = array('TYPO3\Media\Domain\Model\AssetInterface');

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Return a list of sub-properties inside the source object.
     * The "key" is the sub-property name, and the "value" is the value of the sub-property.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        $sourceChildPropertiesToBeConverted = array(
            'resource' => $source->getResource()
        );

        if ($source instanceof \TYPO3\Media\Domain\Model\ImageVariant) {
            unset($sourceChildPropertiesToBeConverted['resource']);
            $sourceChildPropertiesToBeConverted['originalImage'] = $source->getOriginalImage();
            $sourceChildPropertiesToBeConverted['processingInstructions'] = $source->getProcessingInstructions();
        }

        return $sourceChildPropertiesToBeConverted;
    }

    /**
     * Return the type of a given sub-property inside the $targetType, in this case always "array"
     *
     * @param string $targetType is ignored
     * @param string $propertyName is ignored
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration is ignored
     * @return string always "array"
     */
    public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration)
    {
        return 'array';
    }

    /**
     * Convert an object from $source to an array
     *
     * @param AssetInterface $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array The converted asset or NULL
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        switch (true) {
            case $source instanceof \TYPO3\Media\Domain\Model\ImageVariant:
                if (!isset($convertedChildProperties['originalImage']) || !is_array($convertedChildProperties['originalImage'])) {
                    return null;
                }

                $convertedChildProperties['originalImage']['__identity'] = $this->persistenceManager->getIdentifierByObject($source->getOriginalImage());

                return array(
                    'originalImage' => $convertedChildProperties['originalImage'],
                    'processingInstructions' => $convertedChildProperties['processingInstructions']
                );
            case $source instanceof \TYPO3\Media\Domain\Model\AssetInterface:
                if (!isset($convertedChildProperties['resource']) || !is_array($convertedChildProperties['resource'])) {
                    return null;
                }

                $convertedChildProperties['resource']['__identity'] = $this->persistenceManager->getIdentifierByObject($source->getResource());

                return array(
                    'title' => $source->getTitle(),
                    'resource' => $convertedChildProperties['resource']
                );
        }
    }
}
