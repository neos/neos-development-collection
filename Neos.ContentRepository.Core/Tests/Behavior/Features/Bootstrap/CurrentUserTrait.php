<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeUserIdProvider;

/**
 * The feature trait to test projected nodes
 */
trait CurrentUserTrait
{
    /**
     * @Given /^I am user identified by "([^"]*)"$/
     * @param string $userId
     */
    public function iAmUserIdentifiedBy(string $userId): void
    {
        FakeUserIdProvider::setUserId(UserId::fromString($userId));
    }
}
