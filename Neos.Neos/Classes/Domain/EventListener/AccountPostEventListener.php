<?php
namespace Neos\Neos\Domain\EventListener;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Event\LifecycleEventArgs;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Security\Account;

/**
 * Doctrine event listener for clearing the Neos configuration version cache on account changes.
 *
 * @Flow\Scope("singleton")
 */
class AccountPostEventListener
{
    /**
     * @var CacheManager
     * @Flow\Inject
     */
    protected $cacheManager;

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        if ($eventArgs->getEntity() instanceof Account) {
            $this->flushConfigurationCache();
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        if ($eventArgs->getEntity() instanceof Account) {
            $this->flushConfigurationCache();
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postRemove(LifecycleEventArgs $eventArgs)
    {
        if ($eventArgs->getEntity() instanceof Account) {
            $this->flushConfigurationCache();
        }
    }

    /**
     * @return void
     */
    protected function flushConfigurationCache()
    {
        $this->cacheManager->getCache('Neos_Neos_Configuration_Version')->flush();
    }
}
