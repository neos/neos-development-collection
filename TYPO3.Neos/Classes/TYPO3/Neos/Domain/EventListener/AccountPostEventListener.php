<?php
namespace TYPO3\Neos\Domain\EventListener;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Event\LifecycleEventArgs;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Security\Account;

/**
 * Doctrine event listener for clearing configuration cache on account changes.
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
            $this->flushConfigurationCache($eventArgs->getEntity());
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        if ($eventArgs->getEntity() instanceof Account) {
            $this->flushConfigurationCache($eventArgs->getEntity());
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postRemove(LifecycleEventArgs $eventArgs)
    {
        if ($eventArgs->getEntity() instanceof Account) {
            $this->flushConfigurationCache($eventArgs->getEntity());
        }
    }

    /**
     * @return void
     */
    protected function flushConfigurationCache()
    {
        $this->cacheManager->getCache('TYPO3_Neos_Configuration_Version')->flush();
    }
}
