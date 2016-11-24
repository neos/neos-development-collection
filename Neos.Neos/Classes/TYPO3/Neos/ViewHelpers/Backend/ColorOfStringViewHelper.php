<?php
namespace Neos\Neos\ViewHelpers\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Generates a color code for a given string
 */
class ColorOfStringViewHelper extends AbstractViewHelper
{
    /**
     * Outputs a hex color code (#000000) based on $text
     *
     * @param string $string
     * @param integer $minimalBrightness
     * @return string
     * @throws \Exception
     */
    public function render($string = null, $minimalBrightness = 50)
    {
        if ($minimalBrightness < 0 or $minimalBrightness > 255) {
            throw new \Exception('Minimal brightness should be between 0 and 255', 1417553921);
        }

        if ($string === null) {
            $string = $this->renderChildren();
        }

        $hash = md5($string);

        $rgbValues = array();
        for ($i = 0; $i < 3; $i++) {
            $rgbValues[$i] = max(array(
                round(hexdec(substr($hash, 10 * $i, 10)) / hexdec('FFFFFFFFFF') * 255),
                $minimalBrightness
            ));
        }

        $output = '#';
        for ($i = 0; $i < 3; $i++) {
            $output .= str_pad(dechex($rgbValues[$i]), 2, 0, STR_PAD_LEFT);
        }

        return $output;
    }
}
