<?php
namespace TYPO3\Neos\TYPO3CR;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Service\NodeService;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * @Flow\Scope("singleton")
 */
class NeosNodeService extends NodeService implements NeosNodeServiceInterface
{
    /**
     * Normalizes the given node path to a reference path and returns an absolute path.
     *
     * @param string $path The non-normalized path
     * @param string $referencePath a reference path in case the given path is relative.
     * @param string $siteNodePath Reference path to a site node. Relative paths starting with "~" will be based on the siteNodePath.
     * @return string The normalized absolute path
     * @throws \InvalidArgumentException if the node path was invalid.
     */
    public function normalizePath($path, $referencePath = null, $siteNodePath = null)
    {
        if (strpos($path, '~') === 0) {
            $path = NodePaths::addNodePathSegment($siteNodePath, substr($path, 1));
        }
        return parent::normalizePath($path, $referencePath);
    }
}
