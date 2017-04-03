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
use Neos\Media\Domain\Model\ImageVariant;

/**
 * This converter transforms to \Neos\Media\Domain\Model\ImageVariant objects.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageVariantConverter extends ImageInterfaceConverter
{
    /**
     * @var string
     */
    protected $targetType = ImageVariant::class;

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
    protected static $defaultNewAssetType = ImageVariant::class;
}
