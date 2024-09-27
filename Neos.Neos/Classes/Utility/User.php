<?php

declare(strict_types=1);

namespace Neos\Neos\Utility;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\Domain\Service\WorkspaceNameBuilder;
use Neos\Neos\Domain\Service\WorkspaceService;

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
     * @deprecated with Neos 9.0 please use {@see WorkspaceService::createPersonalWorkspaceForUserIfMissing()} instead.
     * @param string $username
     * @return string
     */
    public static function getPersonalWorkspaceNameForUsername($username): string
    {
        $name = preg_replace('/[^A-Za-z0-9\-]/', '-', 'user-' . $username);
        if (is_null($name)) {
            throw new \InvalidArgumentException(
                'Cannot convert account identifier ' . $username . ' to workspace name.',
                1645656253
            );
        }
        return WorkspaceName::transliterateFromString($name)->value;
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
