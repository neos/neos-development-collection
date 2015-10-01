<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Model\User;

/**
 * Test case for the "User" domain model
 *
 */
class UserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorInitializesPreferences()
    {
        $user = new User();
        $this->assertInstanceOf('TYPO3\Neos\Domain\Model\UserPreferences', $user->getPreferences());
    }
}
