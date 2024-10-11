<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Neos\Domain\Service\WorkspaceService;

/**
 * The classification of a workspace – A workspace is always of one of the covered cases
 *
 * @api
 */
enum WorkspaceClassification : string
{
    /**
     * The personal workspace of a Neos user
     */
    case PERSONAL = 'PERSONAL';

    /**
     * A workspace that can potentially be shared by multiple Neos users
     */
    case SHARED = 'SHARED';

    /**
     * A workspace without a target, e.g. the "live" workspace
     */
    case ROOT = 'ROOT';

    /**
     * A non-root content repository workspace without corresponding metadata
     *
     * In case workspaces were created through the content repository and not through Neos' {@see WorkspaceService}
     */
    case UNKNOWN = 'UNKNOWN';
}
