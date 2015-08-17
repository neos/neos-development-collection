<?php
namespace TYPO3\Neos\Controller\Exception;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Neos\Controller\Exception;

/**
 * A "Node not found" exception
 */
class NodeNotFoundException extends Exception {

	/**
	 * @var integer
	 */
	protected $statusCode = 404;

}
