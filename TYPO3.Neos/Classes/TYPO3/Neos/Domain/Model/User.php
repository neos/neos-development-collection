<?php
namespace TYPO3\Neos\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Security\Account;
use TYPO3\Party\Domain\Model\Person;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Domain Model of a User
 *
 * @Flow\Entity
 * @Flow\Scope("prototype")
 * @api
 */
class User extends Person {

	/**
	 * Preferences of this user
	 *
	 * @var UserPreferences
	 * @ORM\OneToOne
	 */
	protected $preferences;

	/**
	 * Constructs this User object
	 */
	public function __construct() {
		parent::__construct();
		$this->preferences = new UserPreferences();
	}

	/**
	 * @return UserPreferences
	 * @api
	 */
	public function getPreferences() {
		return $this->preferences;
	}

	/**
	 * @param UserPreferences $preferences
	 * @return void
	 * @api
	 */
	public function setPreferences(UserPreferences $preferences) {
		$this->preferences = $preferences;
	}

	/**
	 * Checks if at least one account of this user ist active
	 *
	 * @return boolean
	 * @api
	 */
	public function isActive() {
		foreach ($this->accounts as $account) {
			/** @var Account $account */
			if ($account->isActive()) {
				return TRUE;
			}
		}
		return FALSE;
	}

}
