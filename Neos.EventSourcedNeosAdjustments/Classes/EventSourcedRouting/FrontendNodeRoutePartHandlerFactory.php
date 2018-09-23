<?php

namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\EventSourcedContentRepositoryFeatures;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Routing\FrontendNodeRoutePartHandler;

/**
 * Factory (configured in objects.yaml) to switch the RoutePartHandler depending on Feature Flags in CR
 * @Flow\Scope("singleton")
 */
class FrontendNodeRoutePartHandlerFactory
{

    /**
     * @Flow\Inject
     * @var EventSourcedContentRepositoryFeatures
     */
    protected $eventSourcedContentRepositoryFeatures;

    public function create()
    {
        if ($this->eventSourcedContentRepositoryFeatures->isNewRoutingEnabled()) {
            return new EventSourcedFrontendNodeRoutePartHandler();
        } else {
            return new FrontendNodeRoutePartHandler();
        }
    }
}
