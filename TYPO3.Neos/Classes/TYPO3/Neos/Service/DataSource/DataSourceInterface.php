<?php
namespace TYPO3\Neos\Service\DataSource;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Data source interface for providing generic data
 *
 * This is used in the user interface to generate dynamic option lists.
 *
 * @api
 */
interface DataSourceInterface {

	/**
	 * @return string The identifier of the data source
	 * @api
	 */
	static public function getIdentifier();

	/**
	 * Get data
	 *
	 * @param NodeInterface $node The node that is currently edited (optional)
	 * @param array $arguments Additional arguments (key / value)
	 * @return mixed JSON serializable data
	 * @api
	 */
	public function getData(NodeInterface $node = NULL, array $arguments);

}