<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * The feature trait to test projected nodes
 */
trait CurrentUserTrait
{
    protected ?UserIdentifier $currentUserIdentifier = null;

    /**
     * @Given /^I am user identified by "([^"]*)"$/
     * @param string $userIdentifier
     */
    public function iAmUserIdentifiedBy(string $userIdentifier): void
    {
        $this->currentUserIdentifier = UserIdentifier::fromString($userIdentifier);
    }

    public function getCurrentUserIdentifier(): ?UserIdentifier
    {
        return $this->currentUserIdentifier;
    }
}
