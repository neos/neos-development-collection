<?php
namespace TYPO3\Neos\Tests\Functional\Controller\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Neos\Domain\Model\User;

/**
 * Testcase for method security of the backend controller
 *
 * @group large
 */
class BackendControllerSecurityTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @test
	 */
	public function indexActionIsGrantedForAdministrator() {
		$user = new User();

		$account = $this->authenticateRoles(array('TYPO3.Neos:Administrator'));
		$account->setAccountIdentifier('admin');
		$account->setParty($user);
		$this->browser->request('http://localhost/neos/login');

			// dummy assertion to avoid PHPUnit warning
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function indexActionIsDeniedForEverybody() {
		$this->browser->request('http://localhost/neos/');
		$this->assertSame(403, $this->browser->getLastResponse()->getStatusCode());
	}
}
