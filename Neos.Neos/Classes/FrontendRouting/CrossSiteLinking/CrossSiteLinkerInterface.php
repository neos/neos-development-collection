<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\CrossSiteLinking;

use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo;
use Neos\Neos\FrontendRouting\EventSourcedFrontendNodeRoutePartHandler;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 * The {@see CrossSiteLinkerInterface} is responsible for adjusting a built URL in case it needs to
 * be generated for a different Site. It's directly called from {@see EventSourcedFrontendNodeRoutePartHandler}.
 * You will not call implementations of this interface yourself.
 *
 * See {@see EventSourcedFrontendNodeRoutePartHandler} for documentation.
 */
interface CrossSiteLinkerInterface
{
    /**
     * @param DocumentNodeInfo $targetNode the target node where we want to generate the link to
     * @param SiteDetectionResult $currentRequestSiteDetectionResult
     * @return UriConstraints
     */
    public function applyCrossSiteUriConstraints(
        DocumentNodeInfo $targetNode,
        SiteDetectionResult $currentRequestSiteDetectionResult
    ): UriConstraints;
}
