<?php
namespace TYPO3\TYPO3CR;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A class holding utility methods
 *
 * @api
 */
class Utility
{
    /**
     * Transforms a text (for example a node title) into a valid node name by removing invalid characters and
     * transliterating special characters if possible.
     *
     * @param string $name The possibly invalid node name
     * @return string A valid node name
     */
    public static function renderValidNodeName($name)
    {
        // Check if name already match name pattern to prevent unnecessary transliteration
        if (preg_match(NodeInterface::MATCH_PATTERN_NAME, $name) === 1) {
            return $name;
        }

        // Transliterate (transform 北京 to 'Bei Jing')
        $name = \Behat\Transliterator\Transliterator::transliterate($name);

        // Urlization (replace spaces with dash, special special characters)
        $name = \Behat\Transliterator\Transliterator::urlize($name);

        // Ensure only allowed characters are left
        $name = preg_replace('/[^a-z0-9\-]/', '', $name);

        return $name;
    }

    /**
     * Sorts the incoming $dimensionValues array to make sure that before hashing, the ordering is made deterministic.
     * Then, calculates and returns the dimensionsHash.
     *
     * This method is public because it is used inside SiteImportService.
     *
     * @param array $dimensionValues Map of dimension names to dimension values, which will be ordered alphabetically after this method.
     * @return string the calculated DimensionsHash
     */
    public static function sortDimensionValueArrayAndReturnDimensionsHash(array &$dimensionValues)
    {
        foreach ($dimensionValues as &$values) {
            sort($values);
        }
        ksort($dimensionValues);

        return md5(json_encode($dimensionValues));
    }
}
