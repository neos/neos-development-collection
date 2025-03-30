<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Neos\Domain\Service\WorkspaceService;

/**
 * ...
 *
 * @api
 */
enum NeosUserRole : string
{
    case EVERYBODY = 'Neos.Flow:Everybody';
    case AUTHENTICATED_USER = 'Neos.Flow:AuthenticatedUser';
    case ADMINISTRATOR = 'Neos.Neos:Administrator';
    case ABSTRACT_EDITOR = 'Neos.Neos:AbstractEditor';
    case EDITOR = 'Neos.Neos:Editor';
    case LIVE_PUBLISHER = 'Neos.Neos:LivePublisher';
}
