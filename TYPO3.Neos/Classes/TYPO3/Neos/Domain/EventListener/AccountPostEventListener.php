<?php
namespace TYPO3\Neos\Domain\EventListener;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Doctrine event listener for clearing configuration cache on account changes.
 *
 * @Flow\Scope("singleton")
 */
class AccountPostEventListener {

	/**
	 * @var \TYPO3\Flow\Cache\CacheManager
	 * @Flow\Inject
	 */
	protected $cacheManager;

	/**
	 * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
	 * @return void
	 */
	public function postPersist(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs) {
		if ($eventArgs->getEntity() instanceof \TYPO3\Flow\Security\Account) {
			$this->flushConfigurationCache($eventArgs->getEntity());
		}
	}

	/**
	 * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
	 * @return void
	 */
	public function postUpdate(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs) {
		if ($eventArgs->getEntity() instanceof \TYPO3\Flow\Security\Account) {
			$this->flushConfigurationCache($eventArgs->getEntity());
		}
	}

	/**
	 * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
	 * @return void
	 */
	public function postRemove(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs) {
		if ($eventArgs->getEntity() instanceof \TYPO3\Flow\Security\Account) {
			$this->flushConfigurationCache($eventArgs->getEntity());
		}
	}

	/**
	 * @return void
	 */
	protected function flushConfigurationCache() {
		$this->cacheManager->getCache('TYPO3_Neos_Configuration_Version')->flush();
	}

}
