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
abstract class NodePaths
{
    /**
     * Appends the given $nodePathSegment to the $nodePath
     *
     * @param string $nodePath Absolute node path
     * @param string $nodePathSegment Usually a nodeName but could also be a relative node path.
     * @return string
     */
    public static function addNodePathSegment($nodePath, $nodePathSegment)
    {
        $nodePath = rtrim($nodePath, '/');
        if ($nodePathSegment !== '' || $nodePath === '') {
            $nodePath .= '/' . trim($nodePathSegment, '/');
        }

        return $nodePath;
    }

    /**
     * Returns the given absolute node path appended with additional context information (such as the workspace name and dimensions).
     *
     * @param string $path absolute node path
     * @param string $workspaceName
     * @param array $dimensionValues
     * @return string
     */
    public static function generateContextPath($path, $workspaceName, array $dimensionValues = [])
    {
        $contextPath = $path;
        $contextPath .= '@' . $workspaceName;

        if ($dimensionValues !== []) {
            $contextPath .= ';';
            foreach ($dimensionValues as $dimensionName => $innerDimensionValues) {
                $contextPath .= $dimensionName . '=' . implode(',', $innerDimensionValues) . '&';
            }
            $contextPath = substr($contextPath, 0, -1);
        }

        return $contextPath;
    }

    /**
     * Splits the given context path into relevant information, which results in an array with keys:
     * "nodePath", "workspaceName", "dimensions"
     *
     * @param string $contextPath a context path including workspace and/or dimension information.
     * @return array split information from the context path
     * @see generateContextPath()
     */
    public static function explodeContextPath($contextPath)
    {
        preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $contextPath, $matches);
        if (!isset($matches['NodePath'])) {
            throw new \InvalidArgumentException('The given string was not a valid contextPath.', 1431281250);
        }

        $nodePath = $matches['NodePath'];
        $workspaceName = (isset($matches['WorkspaceName']) && $matches['WorkspaceName'] !== '' ? $matches['WorkspaceName'] : 'live');
        $dimensions = isset($matches['Dimensions']) ? static::parseDimensionValueStringToArray($matches['Dimensions']) : [];

        return [
            'nodePath' => $nodePath,
            'workspaceName' => $workspaceName,
            'dimensions' => $dimensions
        ];
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

    /**
     * Determine if the given node path is a context path.
     *
     * @param string $contextPath
     * @return boolean
     */
    public static function isContextPath($contextPath)
    {
        return (strpos($contextPath, '@') !== false);
    }

    /**
     * Get the name for a Node based on the given path.
     *
     * @param string $path
     * @return string
     */
    public static function getNodeNameFromPath($path)
    {
        return $path === '/' ? '' : substr($path, strrpos($path, '/') + 1);
    }

    /**
     * Get the parent path of the given Node path.
     *
     * @param string $path
     * @return string
     */
    public static function getParentPath($path)
    {
        if ($path === '/') {
            $parentPath = '';
        } elseif (strrpos($path, '/') === 0) {
            $parentPath = '/';
        } else {
            $parentPath = substr($path, 0, strrpos($path, '/'));
        }

        return $parentPath;
    }

    /**
     * Does $possibleSubPath begin with $path and so is a subpath or not.
     *
     * @param string $path
     * @param string $possibleSubPath
     * @return boolean
     */
    public static function isSubPathOf($path, $possibleSubPath)
    {
        return (strpos($possibleSubPath, $path) === 0);
    }

    /**
     * Returns the depth of the given Node path.
     * The root node "/" has depth 0, for every segment 1 is added.
     *
     * @param string $path
     * @return integer
     */
    public static function getPathDepth($path)
    {
        return $path === '/' ? 0 : substr_count($path, '/');
    }

    /**
     * Replaces relative path segments ("." or "..") in a given path
     *
     * @param string $path absolute node path with relative path elements ("." or "..").
     * @return string
     */
    public static function replaceRelativePathElements($path)
    {
        $pathSegments = explode('/', $path);
        $absolutePath = '';
        foreach ($pathSegments as $pathSegment) {
            switch ($pathSegment) {
                case '.':
                    continue 2;
                break;
                case '..':
                    $absolutePath = NodePaths::getParentPath($absolutePath);
                break;
                default:
                    $absolutePath = NodePaths::addNodePathSegment($absolutePath, $pathSegment);
                break;
            }
        }

        return $absolutePath;
    }

    /**
     * Get the relative path between the given $parentPath and the given $subPath.
     * Example with "/foo" and "/foo/bar/baz" will return "bar/baz".
     *
     * @param string $parentPath
     * @param string $subPath
     * @return string
     */
    public static function getRelativePathBetween($parentPath, $subPath)
    {
        if (self::isSubPathOf($parentPath, $subPath) === false) {
            throw new \InvalidArgumentException('Given path "' . $parentPath . '" is not the beginning of "' . $subPath .'", cannot get a relative path between them.', 1430075362);
        }

        return trim(substr($subPath, strlen($parentPath)), '/');
    }

    /**
     * Generates a simple random node name.
     *
     * @return string
     */
    public static function generateRandomNodeName()
    {
        return 'node-' . Algorithms::generateRandomString(13, 'abcdefghijklmnopqrstuvwxyz0123456789');
    }

    /**
     * Normalizes the given node path to a reference path and returns an absolute path.
     *
     * You should usually use \Neos\ContentRepository\Domain\Service\NodeService::normalizePath()  because functionality could be overloaded,
     * this is here only for low level operations.
     *
     *
     * @see \Neos\ContentRepository\Domain\Service\NodeService::normalizePath()
     * @param $path
     * @param string $referencePath
     * @return string
     */
    public static function normalizePath($path, $referencePath = null)
    {
        if ($path === '.') {
            return $referencePath;
        }

        if (!is_string($path)) {
            throw new \InvalidArgumentException(sprintf('An invalid node path was specified: is of type %s but a string is expected.', gettype($path)), 1357832901);
        }

        if (strpos($path, '//') !== false) {
            throw new \InvalidArgumentException('Paths must not contain two consecutive slashes.', 1291371910);
        }

        if ($path[0] === '/') {
            $absolutePath = $path;
        } else {
            $absolutePath = NodePaths::addNodePathSegment($referencePath, $path);
        }

        $normalizedPath = NodePaths::replaceRelativePathElements($absolutePath);
        return strtolower($normalizedPath);
    }
}
