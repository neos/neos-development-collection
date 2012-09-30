<?php
namespace TYPO3\TYPO3\Tests\Functional\Controller\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Domain\Model\User;

/**
 * Testcase for method security of the backend controller
 *
 * @group large
 */
class BackendControllerSecurityTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * We need to enable this, so that the database is set up. Otherwise
	 * there will be an error along the lines of:
	 *  "Table 'functional_tests.domain' doesn't exist"
	 *
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var boolean
	 */
	protected $testableHttpEnabled = TRUE;

	/**
	 * @test
	 */
	public function indexActionIsGrantedForAdministrator() {
		$user = new User();
		$user->getPreferences()->set('context.workspace', 'user-admin');

		$account = $this->authenticateRoles(array('Administrator'));
		$account->setParty($user);
		$this->browser->request('http://localhost/typo3/login');
	}

	/**
	 * @test
	 */
	public function indexActionIsDeniedForEverybody() {
		$this->browser->request('http://localhost/typo3/');
		$this->assertSame(403, $this->browser->getLastResponse()->getStatusCode());
	}
}

?>