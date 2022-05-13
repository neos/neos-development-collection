<?php

namespace Neos\Neos\Utility;

use Neos\Neos\Domain\Model\WorkspaceName;

/**
 * Utility functions for dealing with users in the Content Repository.
 */
class User
{
    /**
     * Constructs a personal workspace name for the user with the given username.
     *
     * @param string $username
     * @return string
     */
    public static function getPersonalWorkspaceNameForUsername($username): string
    {
        return (string)WorkspaceName::fromAccountIdentifier($username);
    }

    /**
     * Will reduce the username to ascii alphabet and numbers.
     *
     * @param string $username
     * @return string
     */
    public static function slugifyUsername($username): string
    {
        return preg_replace('/[^a-z0-9]/i', '', $username) ?: '';
    }
}
