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

/**
 * This converter transforms to \TYPO3\Media\Domain\Model\ImageVariant objects.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageVariantConverter extends ImageInterfaceConverter
{
    /**
     * @var string
     */
    protected $targetType = 'TYPO3\Media\Domain\Model\ImageVariant';

    /**
     * @var integer
     */
    protected $priority = 2;

    /**
     * @Flow\Inject
     * @var ProcessingInstructionsConverter
     */
    protected $processingInstructionsConverter;

    /**
     * If creating a new asset from this converter this defines the default type as fallback.
     *
     * @var string
     */
    protected static $defaultNewAssetType = 'TYPO3\Media\Domain\Model\ImageVariant';
}
