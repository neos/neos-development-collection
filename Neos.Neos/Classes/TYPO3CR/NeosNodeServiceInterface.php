<?php
namespace Neos\Neos\TYPO3CR;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Service\NodeServiceInterface;

/**
 * Provides generic methods to manage and work with Nodes
 *
 * @api
 */
interface NeosNodeServiceInterface extends NodeServiceInterface
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
    public function normalizePath($path, $referencePath = null, $siteNodePath = null);
}
