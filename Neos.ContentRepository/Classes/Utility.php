<?php
namespace Neos\ContentRepository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Transliterator\Transliterator;
use Neos\ContentRepository\Domain\Model\NodeInterface;

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

        $originalName = $name;

        // Transliterate (transform 北京 to 'Bei Jing')
        $name = Transliterator::transliterate($name);

        // Urlization (replace spaces with dash, special special characters)
        $name = Transliterator::urlize($name);

        // Ensure only allowed characters are left
        $name = preg_replace('/[^a-z0-9\-]/', '', $name);

        // Make sure we don't have an empty string left.
        if ($name === '') {
            $name = 'node-' . strtolower(md5($originalName));
        }

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

    /**
     * Generate a stable identifier for auto-created child nodes
     *
     * This is needed if multiple node variants are created through "createNode" with different dimension values. If
     * child nodes with the same path and different identifiers exist, bad things can happen.
     *
     * @param string $childNodeName
     * @param string $identifier Identifier of the node where the child node should be created
     * @return string The generated UUID like identifier
     */
    public static function buildAutoCreatedChildNodeIdentifier($childNodeName, $identifier)
    {
        $hex = md5($identifier . '-' . $childNodeName);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }
}
