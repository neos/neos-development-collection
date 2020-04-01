<?php
namespace Neos\Neos\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Model\UserPreferences;

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
        self::assertInstanceOf(UserPreferences::class, $user->getPreferences());
    }
}
