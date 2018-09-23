<?php

namespace Neos\EventSourcedContentRepository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * This class can be checked to see
 *
 * @api
 * @Flow\Scope("singleton")
 */
class EventSourcedContentRepositoryFeatures
{

    /**
     * @var boolean
     * @Flow\InjectConfiguration("features.newRoutingEnabled")
     */
    protected $newRoutingEnabled;

    public function isNewRoutingEnabled(): bool
    {
        return (boolean)$this->newRoutingEnabled;
    }
}
