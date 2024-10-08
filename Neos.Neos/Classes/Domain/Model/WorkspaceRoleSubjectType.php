<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * @api
 */
enum WorkspaceRoleSubjectType : string
{
    case USER = 'USER';
    case GROUP = 'GROUP';
}
