<?php
namespace TYPO3\Neos\Utility;

/**
 *
 */
class User
{
    /**
     * Will reduce the username to ascii alphabet and numbers.
     *
     * @param string $username
     * @return string
     */
    public static function slugifyUsername($username)
    {
        return preg_replace('/[^a-z0-9]/i', '', $username);
    }
}
