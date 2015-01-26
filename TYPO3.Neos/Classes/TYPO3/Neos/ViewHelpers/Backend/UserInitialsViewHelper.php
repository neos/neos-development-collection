<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

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
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Security\Context;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Party\Domain\Model\Person;

/**
 * Render user initials for a given username
 *
 * This ViewHelper is *WORK IN PROGRESS* and *NOT STABLE YET*
 */
class UserInitialsViewHelper extends AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var Translator
	 */
	protected $translator;

	/**
	 * @Flow\InjectConfiguration("userInterface.defaultLanguage")
	 * @var string
	 */
	protected $defaultLanguageIdentifier;

	/**
	 * Render user initials or an abbreviated name for a given username. If the account was deleted, use the username as fallback.
	 *
	 * @param string $format Supported are "fullFirstName" and "initials"
	 * @return string
	 */
	public function render($format = 'initials') {
		if (!in_array($format, array('fullFirstName', 'initials', 'fullName'))) {
			throw new \InvalidArgumentException(sprintf('Format "%s" given to history:userInitials(), only supporting "fullFirstName" and "initials".', $format), 1415705861);
		}

		$accountIdentifier = $this->renderChildren();

		// TODO: search by credential source is still needed
		/* @var $account \TYPO3\Flow\Security\Account */
		$account = $this->accountRepository->findOneByAccountIdentifier($accountIdentifier);

		if ($account === NULL) {
			return $accountIdentifier;
		}

		/* @var $requestedUser Person */
		$requestedUser = $account->getParty();

		if ($requestedUser === NULL || $requestedUser->getName() === NULL) {
			return $accountIdentifier;
		}

		if ($this->securityContext->canBeInitialized()) {
			if ($this->securityContext->getAccount()) {
				/** @var User $currentUser */
				$currentUser = $this->securityContext->getAccount()->getParty();
				if ($currentUser === $requestedUser) {
					$languageIdentifier = $currentUser->getPreferences()->get('interfaceLanguage') ? $currentUser->getPreferences()->get('interfaceLanguage') : $this->defaultLanguageIdentifier;
					$you = $translation = $this->translator->translateById('you', array(), 1, new Locale($languageIdentifier), 'Main', 'TYPO3.Neos');
				}
			}
		}

		switch ($format) {
			case 'initials':
				return mb_substr($requestedUser->getName()->getFirstName(), 0, 1) . mb_substr($requestedUser->getName()->getLastName(), 0, 1);
			case 'fullFirstName':
			return isset($you) ? $you : $requestedUser->getName()->getFirstName() . ' ' . mb_substr($requestedUser->getName()->getLastName(), 0, 1) . '.';
			case 'fullName':
				return isset($you) ? $you : $requestedUser->getName()->getFullName();
		}
	}
}