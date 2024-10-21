<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * Type of workspace role subject
 *
 * A workspace role can be assigned to a single user or a group (defined by the Flow role identifier)
 *
 * @api
 */
enum WorkspaceRoleSubjectType : string
{
    case USER = 'USER';
    case GROUP = 'GROUP';
}
