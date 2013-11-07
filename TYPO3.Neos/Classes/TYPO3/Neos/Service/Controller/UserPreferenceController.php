<?php
namespace TYPO3\Neos\Service\Controller;

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
use TYPO3\Flow\Security\Context;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Party\Domain\Repository\PartyRepository;

/**
 * Service Controller for user preferences
 */
class UserPreferenceController extends AbstractServiceController {

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var PartyRepository
	 */
	protected $partyRepository;

	/**
	 * @return void
	 */
	public function indexAction() {
		/** @var $user User */
		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
		$this->view->assign('userPreferences', $user->getPreferences());
	}

	/**
	 * Update/adds a user preference
	 *
	 * @param string $key The key of the preference to update/add
	 * @param string $value The value of the preference
	 * @return void
	 */
	public function updateAction($key, $value) {
		/** @var $user User */
		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');

		// TODO: This should be done in an earlier stage (TypeConverter ?)
		if (strtolower($value) === 'false') {
			$value = FALSE;
		} elseif (strtolower($value) === 'true') {
			$value = TRUE;
		}

		$user->getPreferences()->set($key, $value);
		$this->partyRepository->update($user);
		$this->throwStatus(204, 'User preferences have been updated');
	}

}
