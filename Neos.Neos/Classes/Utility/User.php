<?php

namespace Neos\Neos\Utility;

use Neos\Neos\Domain\Service\WorkspaceNameBuilder;

/**
 * Utility functions for dealing with users in the Content Repository.
 *
 * @deprecated with Neos 9.0 please use the respective replacements instead.
 */
class User
{
    /**
     * Constructs a personal workspace name for the user with the given username.
     *
     * @deprecated with Neos 9.0 please use {@see WorkspaceNameBuilder::fromAccountIdentifier} instead.
     * @param string $username
     * @return string
     */
    public static function getPersonalWorkspaceNameForUsername($username): string
    {
        return WorkspaceNameBuilder::fromAccountIdentifier($username)->value;
    }

    /**
     * Will reduce the username to ascii alphabet and numbers.
     *
     * @deprecated with Neos 9.0 please implement your own slug genration. You might also want to look into transliteration with {@see \Behat\Transliterator\Transliterator}.
     * @param string $username
     * @return string
     */
    public static function slugifyUsername($username): string
    {
        return preg_replace('/[^a-z0-9]/i', '', $username) ?: '';
    }
}
