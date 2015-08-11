<?php
namespace TYPO3\Neos\EventLog\Integrations;

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
use TYPO3\Neos\EventLog\Domain\Service\EventEmittingService;

abstract class AbstractIntegrationService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
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
	protected function initializeAccountIdentifier() {
		if ($this->securityContext->canBeInitialized()) {
			$account = $this->securityContext->getAccount();
			if ($account !== NULL) {
				$this->eventEmittingService->setCurrentAccountIdentifier($account->getAccountIdentifier());
			}
		}
	}
}