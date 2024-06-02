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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

use Neos\ContentRepository\Core\ContentRepository;

/**
 * Filter for the {@see ContentRepository::getWorkspaces()} method
 * @api
 */
final readonly class WorkspaceFilter
{
    private function __construct(
        public ?WorkspaceStatus $status,
    ) {
    }

    public static function create(
        WorkspaceStatus|null $status = null,
    ): self {
        return new self(
            $status,
        );
    }
}
