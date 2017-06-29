<?php
namespace Neos\Neos\Utility;

use Neos\ContentRepository\Domain\Model\Workspace;

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
    public static function getPersonalWorkspaceNameForUsername($username)
    {
        return Workspace::PERSONAL_WORKSPACE_PREFIX . static::slugifyUsername($username);
    }

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
