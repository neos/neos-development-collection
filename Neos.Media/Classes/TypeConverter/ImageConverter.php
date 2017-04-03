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
use Neos\Media\Domain\Model\Image;

/**
 * This converter transforms to \Neos\Media\Domain\Model\Image objects.
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
