<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * @api
 */
enum WorkspaceSubjectType
{
    case USER;
    case GROUP;
}
