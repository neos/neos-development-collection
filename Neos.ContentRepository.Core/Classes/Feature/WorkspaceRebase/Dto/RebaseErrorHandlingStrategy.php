<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto;

/**
 * The strategy how to handle errors during workspace rebase
 *
 * - fail (default) ensures conflicts are not ignored but reported
 * - force will rebase even if some conflicting events could have to be rebased
 *
 * @api DTO of {@see RebaseWorkspace} command
 */
enum RebaseErrorHandlingStrategy: string implements \JsonSerializable
{
    /**
     * This strategy rebasing will fail if conflicts are detected and the "WorkspaceRebaseFailed" event is added.
     */
    case STRATEGY_FAIL = 'fail';

    /**
     * This strategy means all events that can be applied are rebased and conflicting events are ignored
     */
    case STRATEGY_FORCE = 'force';

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
