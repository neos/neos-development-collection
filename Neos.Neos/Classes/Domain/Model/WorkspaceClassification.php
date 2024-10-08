<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * The classification of a workspace – A workspace is always of one of the covered cases
 *
 * @api
 */
enum WorkspaceClassification : string
{
    case PERSONAL = 'PERSONAL'; // The personal workspace of a Neos user
    case SHARED = 'SHARED'; // A workspace that can potentially be shared by multiple Neos users
    case ROOT = 'ROOT'; // A workspace without a target, e.g. the "live" workspace

    case UNKNOWN = 'UNKNOWN'; // This case represents a classification that could not be determined (i.e. no corresponding workspace metadata exists)
}
