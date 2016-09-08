<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Model;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Neos\Domain\Model\UserPreferences;

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
        $this->assertInstanceOf(UserPreferences::class, $user->getPreferences());
    }
}
