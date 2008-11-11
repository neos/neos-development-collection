<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::FLOW3::Persistence;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 */

/**
 * The QueryFactory used to create queries against the storage backend
 *
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class QueryFactory implements F3::FLOW3::Persistence::QueryFactoryInterface {

	/**
	 * @var F3::FLOW3::Object::FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * Injects the FLOW3 object factory
	 *
	 * @param F3::FLOW3::Object::FactoryInterface $objectFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectFactory(F3::FLOW3::Object::FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Creates a query object working on the given class name
	 *
	 * @param string $className
	 * @return F3::TYPO3CR::FLOW3::Persistence::Query
	 */
	public function create($className) {
		return $this->objectFactory->create('F3::TYPO3CR::FLOW3::Persistence::Query', $className);
	}

}
?>