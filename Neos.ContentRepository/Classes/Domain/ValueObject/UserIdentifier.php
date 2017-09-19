<?php
namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * User Identifier
 */
final class UserIdentifier extends AbstractIdentifier
{
    const SYSTEM_USER_IDENTIFIER = '00000000-0000-0000-0000-000000000000';

    /**
     * Creates a special user identifier which refers to the virtual "system" user.
     */
    static public function forSystemUser()
    {
        return self::fromString(self::SYSTEM_USER_IDENTIFIER);
    }

    /**
     * @return bool
     */
    public function isSystemUser(): bool
    {
        return $this->uuid === self::SYSTEM_USER_IDENTIFIER;
    }
}
