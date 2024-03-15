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

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal (slightly hacky) implementation details for the workspace command handler
 */
class ContentStreamIdOverride
{
    /**
     * A content stream id that is used instead of the workspace one's {@see ConstraintChecks::requireContentStream()}
     */
    private static ?ContentStreamId $contentStreamIdToUse = null;

    /**
     * Makes the given content stream id available to be used in the given function {@see WorkspaceCommandHandler::handleRebaseWorkspace()}
     * @internal
     */
    public static function applyContentStreamIdToClosure(ContentStreamId $contentStreamIdToUse, \Closure $fn): void
    {
        if (self::$contentStreamIdToUse !== null) {
            throw new \Exception('Recursive content stream override is not supported', 1710426945);
        }
        self::$contentStreamIdToUse = $contentStreamIdToUse;
        try {
            $fn();
        } finally {
            self::$contentStreamIdToUse = null;
        }
    }

    /**
     * @internal
     */
    public static function resolveContentStreamIdForWorkspace(ContentRepository $contentRepository, WorkspaceName $workspaceName): ContentStreamId
    {
        $contentStreamId = self::$contentStreamIdToUse
            ?: $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName)?->currentContentStreamId;

        if (!$contentStreamId) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream for workspace "' . $workspaceName->value . '" does not exist yet.',
                1710407870
            );
        }

        return $contentStreamId;
    }
}
