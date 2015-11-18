<?php
namespace TYPO3\Neos\Domain\Model;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
class User extends Person
{
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
    public function __construct()
    {
        parent::__construct();
        $this->preferences = new UserPreferences();
    }

    /**
     * @return UserPreferences
     * @api
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * @param UserPreferences $preferences
     * @return void
     * @api
     */
    public function setPreferences(UserPreferences $preferences)
    {
        $this->preferences = $preferences;
    }

    /**
     * Checks if at least one account of this user ist active
     *
     * @return boolean
     * @api
     */
    public function isActive()
    {
        foreach ($this->accounts as $account) {
            /** @var Account $account */
            if ($account->isActive()) {
                return true;
            }
        }
        return false;
    }
}
