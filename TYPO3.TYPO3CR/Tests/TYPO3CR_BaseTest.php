<?php
declare(encoding = 'utf-8');

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
 * Tests for the Node implementation of TYPO3CR
 *
 * @package		TYPO3CR
 * @subpackage	Tests
 * @version 	$Id: TYPO3CR_NodeTest.php 296 2007-08-11 17:12:40Z ronny $
 * @author 		Ronny Unger <ru@php-workx.de>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_BaseTest extends T3_Testing_BaseTestCase {
	/**
	 * @var T3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var T3_TYPO3CR_Repository
	 */
	protected $repository;

	/**
	 * @var T3_TYPO3CR_Session
	 */
	protected $session;
	
	/**
	 * create new instance of base unit test for typo3cr
	 * 
	 * often needed components are stored globally between tests
	 * to speed up the test performance.
	 * 
	 * @author 		Ronny Unger <ru@php-workx.de>
	 */
	public function __construct() {
		if (!isset($GLOBALS['COMPONENT_MANAGER'])) {
			$TYPO3 = new T3_FLOW3;
			$TYPO3->initialize();
			$this->componentManager = $TYPO3->getComponentManager();
			$GLOBALS['COMPONENT_MANAGER'] = $this->componentManager;
		} else {
			$this->componentManager = $GLOBALS['COMPONENT_MANAGER'];
		}

		if (!isset($GLOBALS['REPOSITORY'])) {
			$this->repository = $this->componentManager->getComponent('T3_phpCR_RepositoryInterface');
			$GLOBALS['REPOSITORY'] = $this->repository;
		} else {
			$this->repository = $GLOBALS['REPOSITORY'];
		}

		if (!isset($GLOBALS['SESSION'])) {
			$this->session = $this->repository->login();
			$GLOBALS['SESSION'] = $this->session;
		} else {
			$this->session = $GLOBALS['SESSION'];
		}
	}
	
	/**
	 * without dummy test method, PHPUNIT throws a test error
	 */
	public function testDummyAssert() {
		$this->assertTrue(true);
	}
}
?>
