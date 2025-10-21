<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

/**
 * @api part of the subscription status
 */
enum SubscriptionStatus : string
{
    /**
     * New subscribers e.g a newly installed package: will not be run on catchup active as it doesn't have its schema setup
     */
    case NEW = 'NEW';
    /**
     * Subscriber was set up and can be catch-up via boot, but will not run on active
     */
    case BOOTING = 'BOOTING';
    /**
     * Active subscribers will always be run if new events are commited
     */
    case ACTIVE = 'ACTIVE';
    /**
     * Subscribers that are uninstalled will be detached and have to be reactivated
     */
    case DETACHED = 'DETACHED';
    /**
     * Subscribers that are run into an error during catchup or setup
     */
    case ERROR = 'ERROR';
}
