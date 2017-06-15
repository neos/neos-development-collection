<?php
namespace Neos\ContentRepository\Domain\Utility;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Provides basic logic concerning contexts.
 */
abstract class NodeContexts
{

    /**
     * Determine if the given node path is a context path.
     *
     * @param string $string path or identifier
     * @return boolean
     */
    public static function hasContext($string)
    {
        return (strpos($string, '@') !== false);
    }

    /**
     * @param array $dimensionValues
     * @return string
     */
    public static function parseDimensionValuesToString(array $dimensionValues = array())
    {
        $dimensionString = '';
        if ($dimensionValues !== array()) {
            foreach ($dimensionValues as $dimensionName => $innerDimensionValues) {
                $dimensionString .= $dimensionName . '=' . implode(',', $innerDimensionValues) . '&';
            }
        }
        return $dimensionString;
    }

    /**
     * @param string $dimensionValueString
     * @return array
     */
    public static function parseDimensionValueStringToArray($dimensionValueString)
    {
        parse_str($dimensionValueString, $dimensions);
        $dimensions = array_map(function ($commaSeparatedValues) {
            return explode(',', $commaSeparatedValues);
        }, $dimensions);

        return $dimensions;
    }
}
