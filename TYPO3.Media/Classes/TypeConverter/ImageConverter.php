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
use TYPO3\Media\Domain\Model\Image;

/**
 * This converter transforms to \TYPO3\Media\Domain\Model\Image objects.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageConverter extends ImageInterfaceConverter
{
    /**
     * @var string
     */
    protected $targetType = Image::class;

    /**
     * @var integer
     */
    protected $priority = 2;
}
