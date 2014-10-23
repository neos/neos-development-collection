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

/**
 * Data source interface for getting data.
 *
 * @api
 */
abstract class AbstractDataSource implements DataSourceInterface {

	/**
	 * The identifier of the operation
	 *
	 * @var string
	 * @api
	 */
	static protected $identifier = NULL;

	/**
	 * @return string the short name of the operation
	 * @api
	 * @throws \TYPO3\Neos\Exception
	 */
	static public function getIdentifier() {
		if (!is_string(static::$identifier)) {
			throw new \TYPO3\Neos\Exception('Identifier in class ' . __CLASS__ . ' is empty.', 1414090236);
		}
		return static::$identifier;
	}

}