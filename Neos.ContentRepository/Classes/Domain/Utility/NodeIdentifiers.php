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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Utility\Algorithms;

/**
 * Provides basic logic concerning node paths.
 */
abstract class NodeIdentifiers
{
    /**
     * Returns the given absolute node path appended with additional context information (such as the workspace name and dimensions).
     *
     * @param string $identifier node identifier
     * @param string $workspaceName
     * @param array $dimensionValues
     * @return string
     */
    public static function generateContextIdentifier($identifier, $workspaceName, array $dimensionValues = array())
    {
        $contextIdentifier = $identifier;
        $contextIdentifier .= '@' . $workspaceName;

        if ($dimensionValues !== array()) {
            $contextIdentifier .= ';' . NodeContexts::parseDimensionValuesToString($dimensionValues);
        }

        return $contextIdentifier;
    }

    /**
     * Splits the given context path into relevant information, which results in an array with keys:
     * "nodePath", "workspaceName", "dimensions"
     *
     * @param string $contextIdentifier a context path including workspace and/or dimension information.
     * @return array split information from the context path
     * @see generateContextPath()
     */
    public static function explodeContextIdentifier($contextIdentifier)
    {
        preg_match(NodeInterface::MATCH_PATTERN_CONTEXTIDENTIFIER, $contextIdentifier, $matches);
        if (!isset($matches['NodeIdentifier'])) {
            throw new \InvalidArgumentException('The given string was not a valid contextIdentifier.', 1497457303);
        }

        $nodeIdentifier = $matches['NodeIdentifier'];
        $workspaceName = (isset($matches['WorkspaceName']) && $matches['WorkspaceName'] !== '' ? $matches['WorkspaceName'] : 'live');
        $dimensions = isset($matches['Dimensions']) ? NodeContexts::parseDimensionValueStringToArray($matches['Dimensions']) : array();

        return array(
            'nodeIdentifier' => $nodeIdentifier,
            'workspaceName' => $workspaceName,
            'dimensions' => $dimensions
        );
    }
}
