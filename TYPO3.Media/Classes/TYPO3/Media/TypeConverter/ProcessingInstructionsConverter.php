<?php
namespace TYPO3\Media\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Converts an array of processing instructions to matching adjustments
 */
class ProcessingInstructionsConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = array('array');

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var integer
     */
    protected $priority = 0;

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * The return value can be one of three types:
     * - an arbitrary object, or a simple type (which has been created while mapping).
     *   This is the normal case.
     * - NULL, indicating that this object should *not* be mapped (i.e. a "File Upload" Converter could return NULL if no file has been uploaded, and a silent failure should occur.
     * - An instance of \TYPO3\Flow\Error\Error -- This will be a user-visible error message later on.
     * Furthermore, it should throw an Exception if an unexpected failure (like a security error) occurred or a configuration issue happened.
     *
     * @param array $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return array the target type, or an error object if a user-error occurred
     * @throws \TYPO3\Flow\Property\Exception\TypeConverterException thrown in case a developer error occurred
     * @api
     */
    public function convertFrom($source, $targetType = 'array', array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        $result = array();
        foreach ($source as $processingInstruction) {
            if ($processingInstruction['command'] !== '') {
                $adjustment = null;
                switch ($processingInstruction['command']) {
                    case 'crop':
                        $options = array();
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'start.x', 'x');
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'start.y', 'y');
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'size.width', 'width');
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'size.height', 'height');
                        $adjustment = new \TYPO3\Media\Domain\Model\Adjustment\CropImageAdjustment($options);
                        break;
                    case 'resize':
                        $options = array();
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'size.width', 'width');
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'size.height', 'height');
                        $adjustment = new \TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment($options);
                        break;
                }

                if ($adjustment !== null) {
                    $result[] = $adjustment;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $commandOptions
     * @param array $adjustmentOptions
     * @param string $commandOptionPath
     * @param string $adjustmentOptionName
     * @return void
     */
    protected function transferOptionFromCommandToAdjustment(array $commandOptions, array &$adjustmentOptions, $commandOptionPath, $adjustmentOptionName)
    {
        $commandOptionValue = \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($commandOptions, $commandOptionPath);
        if ($commandOptionValue !== null) {
            \TYPO3\Flow\Reflection\ObjectAccess::setProperty($adjustmentOptions, $adjustmentOptionName, $commandOptionValue);
        }
    }
}
