<?php
namespace Neos\Neos\Utility;

/**
 * Utility functions for dealing with users in the Content Repository.
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
