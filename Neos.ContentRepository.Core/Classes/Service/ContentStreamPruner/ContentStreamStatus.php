<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service\ContentStreamPruner;

/**
 * @api
 */
enum ContentStreamStatus: string
{
    /**
     * the content stream was created, but not yet assigned to a workspace.
     *
     * **temporary state** which should not appear if the system is idle (for content streams which are used with workspaces).
     */
    case CREATED = 'created';

    /**
     * FORKED means the content stream was forked from an existing content stream, but not yet assigned
     * to a workspace.
     *
     * **temporary state** which should not appear if the system is idle (for content streams which are used with workspaces).
     */
    case FORKED = 'forked';

    /**
     * the content stream is currently referenced as the "active" content stream by a workspace.
     */
    case IN_USE_BY_WORKSPACE = 'in use by workspace';

    /**
     * the content stream is not used anymore, and can be removed.
     */
    case NO_LONGER_IN_USE = 'no longer in use';

    public function isTemporary(): bool
    {
        return match ($this) {
            self::CREATED, self::FORKED => true,
            default => false
        };
    }
}
