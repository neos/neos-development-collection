<?php
namespace TYPO3\Neos\TYPO3CR;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Service\NodeServiceInterface;

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
