<?php
namespace TYPO3\TYPO3\Tests\Functional\Controller\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Domain\Model\User;

/**
 * Testcase for method security of the backend controller
 *
 */
class BackendControllerSecurityTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {



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
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function indexActionIsGrantedForAdministrator() {
		$user = new User();
		$user->getPreferences()->set('context.workspace', 'user-admin');

		$account = $this->authenticateRoles(array('Administrator'));
		$account->setParty($user);
		$this->sendWebRequest('Backend\Backend', 'TYPO3.TYPO3', 'index');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \TYPO3\FLOW3\Security\Exception\AccessDeniedException
	 */
	public function indexActionIsDeniedForEverybody() {
		$this->sendWebRequest('Backend\Backend', 'TYPO3.TYPO3', 'index');
	}
}

?>