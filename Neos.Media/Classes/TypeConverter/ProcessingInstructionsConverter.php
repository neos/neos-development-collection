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
use Neos\Flow\Property\Exception\TypeConverterException;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Utility\ObjectAccess;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\Adjustment\ResizeImageAdjustment;

/**
 * Converts an array of processing instructions to matching adjustments
 */
class ProcessingInstructionsConverter extends AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = ['array'];

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
     * - An instance of \Neos\Error\Messages\Error -- This will be a user-visible error message later on.
     * Furthermore, it should throw an Exception if an unexpected failure (like a security error) occurred or a configuration issue happened.
     *
     * @param array $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array the target type, or an error object if a user-error occurred
     * @throws TypeConverterException thrown in case a developer error occurred
     * @api
     */
    public function convertFrom($source, $targetType = 'array', array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $result = [];
        foreach ($source as $processingInstruction) {
            if ($processingInstruction['command'] !== '') {
                $adjustment = null;
                switch ($processingInstruction['command']) {
                    case 'crop':
                        $options = [];
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'start.x', 'x');
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'start.y', 'y');
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'size.width', 'width');
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'size.height', 'height');
                        $adjustment = new CropImageAdjustment($options);
                        break;
                    case 'resize':
                        $options = [];
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'size.width', 'width');
                        $this->transferOptionFromCommandToAdjustment($processingInstruction['options'], $options, 'size.height', 'height');
                        $adjustment = new ResizeImageAdjustment($options);
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
        $commandOptionValue = ObjectAccess::getPropertyPath($commandOptions, $commandOptionPath);
        if ($commandOptionValue !== null) {
            ObjectAccess::setProperty($adjustmentOptions, $adjustmentOptionName, $commandOptionValue);
        }
    }
}
