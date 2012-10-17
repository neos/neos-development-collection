<?php
namespace TYPO3\TYPO3\Tests\Unit\Domain\Model;

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
 * Testcase for the "User" domain model
 *
 */
class UserTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function constructorInitializesPreferences() {
		$user = new \TYPO3\TYPO3\Domain\Model\User();
		$this->assertInstanceOf('TYPO3\TYPO3\Domain\Model\UserPreferences', $user->getPreferences());
	}

}

?>