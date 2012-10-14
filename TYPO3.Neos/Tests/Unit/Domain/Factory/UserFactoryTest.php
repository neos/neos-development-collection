<?php
namespace TYPO3\TYPO3\Tests\Unit\Domain\Factory;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the UserFactory
 *
 */
class UserFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function createSetsPersonName() {
		$mockAccount = $this->getMock('TYPO3\Flow\Security\Account');
		$mockAccountFactory = $this->getMock('TYPO3\Flow\Security\AccountFactory');
		$mockAccountFactory->expects($this->any())->method('createAccountWithPassword')->will($this->returnValue($mockAccount));

		$factory = new \TYPO3\TYPO3\Domain\Factory\UserFactory();
		$this->inject($factory, 'accountFactory', $mockAccountFactory);

		$user = $factory->create('username', 'password', 'John', 'Doe');

		$this->assertEquals('John Doe', $user->getName()->getFullName());
	}

	/**
	 * @test
	 */
	public function createAlsoCreatesAccount() {
		$mockAccount = $this->getMock('TYPO3\Flow\Security\Account');
		$mockAccountFactory = $this->getMock('TYPO3\Flow\Security\AccountFactory');

		$factory = new \TYPO3\TYPO3\Domain\Factory\UserFactory();
		$this->inject($factory, 'accountFactory', $mockAccountFactory);

		$mockAccountFactory->expects($this->atLeastOnce())->method('createAccountWithPassword')->with('username', 'password', array('Editor'), 'Typo3BackendProvider')->will($this->returnValue($mockAccount));

		$user = $factory->create('username', 'password', 'John', 'Doe');
	}

	/**
	 * @test
	 */
	public function createSetsWorkspaceNamePreference() {
		$mockAccount = $this->getMock('TYPO3\Flow\Security\Account');
		$mockAccountFactory = $this->getMock('TYPO3\Flow\Security\AccountFactory');
		$mockAccountFactory->expects($this->any())->method('createAccountWithPassword')->will($this->returnValue($mockAccount));

		$factory = new \TYPO3\TYPO3\Domain\Factory\UserFactory();
		$this->inject($factory, 'accountFactory', $mockAccountFactory);

		$user = $factory->create('user_name', 'password', 'John', 'Doe');

		$this->assertEquals('user-username', $user->getPreferences()->get('context.workspace'));
	}

}

?>