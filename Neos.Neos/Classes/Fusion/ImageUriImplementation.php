<?php
namespace Neos\Neos\Fusion;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Exception;
use Neos\Media\Fusion\ImageImplementation;

/**
 * Render an AssetInterface: object. Accepts the same parameters as the uri.image ViewHelper of the Neos.Media package:
 * asset, width, maximumWidth, height, maximumHeight, allowCropping, allowUpScaling.
 *
 * @deprecated This class will be replaced by Neos\Media\ImageImplementation
 */
class ImageUriImplementation extends ImageImplementation
{
    /**
     * @return string
     * @throws Exception
     */
    public function evaluate()
    {
        $parentEvaulation = parent::evaluate();
        return array_key_exists('src', $parentEvaulation) ? $parentEvaulation['src'] : '';
    }
}
