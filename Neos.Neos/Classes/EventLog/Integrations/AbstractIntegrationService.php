<?php
namespace Neos\Neos\EventLog\Integrations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context;
use Neos\Neos\EventLog\Domain\Service\EventEmittingService;

abstract class AbstractIntegrationService
{
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var EventEmittingService
     */
    protected $eventEmittingService;

    /**
     * Try to set the current account identifier emitting the events, if possible
     *
     * @return void
     */
    protected function initializeAccountIdentifier()
    {
        if ($this->securityContext->canBeInitialized()) {
            $account = $this->securityContext->getAccount();
            if ($account !== null) {
                $this->eventEmittingService->setCurrentAccountIdentifier($account->getAccountIdentifier());
            }
        }
    }
}
