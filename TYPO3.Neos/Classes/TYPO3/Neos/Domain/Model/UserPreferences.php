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

use TYPO3\Flow\Annotations as Flow;

/**
 * A preferences container for a user.
 *
 * This is a very naïve, rough and temporary implementation of a User Preferences container.
 * We'll need a better one which understands which options are available and contains some
 * information about possible help texts etc.
 *
 * @Flow\Entity
 * @Flow\Scope("prototype")
 * @todo Provide a more capable implementation
 */
class UserPreferences
{
    /**
     * The actual settings
     *
     * @var array
     */
    protected $preferences = array();

    /**
     * Get preferences
     *
     * @return array
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->preferences[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return isset($this->preferences[$key]) ? $this->preferences[$key] : null;
    }
}
