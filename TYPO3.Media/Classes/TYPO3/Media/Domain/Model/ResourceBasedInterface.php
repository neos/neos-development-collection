<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Base interface for any class which is based on a Resource object.
 */
interface ResourceBasedInterface
{
    /**
     * Returns the Resource object
     *
     * @return \TYPO3\Flow\Resource\Resource
     */
    public function getResource();

    /**
     * Refreshes this asset after the Resource has been modified
     *
     * @return void
     */
    public function refresh();
}
