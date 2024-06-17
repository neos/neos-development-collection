<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * @api
 */
enum WorkspaceClassification : string
{
    case PERSONAL = 'PERSONAL';
    case SHARED = 'SHARED';
    case ROOT = 'ROOT';

    case UNKNOWN = 'UNKNOWN';
}
