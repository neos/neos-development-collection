<?php
namespace TYPO3\Neos\Tests\Unit\Command;

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
 * Testcase for the "UserCommandController"
 *
 */
class UserCommandControllerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function createCommandCreatesAndAddsUser() {
		$mockUserFactory = $this->getMock('TYPO3\Neos\Domain\Factory\UserFactory');
		$mockPartyRepository = $this->getMock('TYPO3\Party\Domain\Repository\PartyRepository');
		$mockAccountRepository = $this->getMock('TYPO3\Flow\Security\AccountRepository');
		$mockResponse = $this->getMock('TYPO3\Flow\Cli\Response');
		$mockUser = $this->getMock('TYPO3\Neos\Domain\Model\User');
		$mockAccount = $this->getMock('TYPO3\Flow\Security\Account');
		$mockUser->expects($this->any())->method('getAccounts')->will($this->returnValue(array($mockAccount)));

		$controller = new \TYPO3\Neos\Command\UserCommandController();
		$this->inject($controller, 'response', $mockResponse);
		$this->inject($controller, 'userFactory', $mockUserFactory);
		$this->inject($controller, 'partyRepository', $mockPartyRepository);
		$this->inject($controller, 'accountRepository', $mockAccountRepository);

		$mockUserFactory->expects($this->once())->method('create')->with('username', 'password', 'John', 'Doe', array('Editor'))->will($this->returnValue($mockUser));
		$mockPartyRepository->expects($this->once())->method('add')->with($mockUser);
		$mockAccountRepository->expects($this->once())->method('add')->with($mockAccount);

		$controller->createCommand('username', 'password', 'John', 'Doe');
	}

}

?>