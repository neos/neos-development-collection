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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo;
use Neos\Neos\FrontendRouting\EventSourcedFrontendNodeRoutePartHandler;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 * Default implementation for cross-site linking.
 *
 * See {@see EventSourcedFrontendNodeRoutePartHandler} for documentation.
 *
 * @Flow\Scope("singleton")
 * @internal
 */
final class CrossSiteLinker implements CrossSiteLinkerInterface
{
    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    public function applyCrossSiteUriConstraints(
        DocumentNodeInfo $targetNode,
        SiteDetectionResult $currentRequestSiteDetectionResult
    ): UriConstraints {
        $uriConstraints = UriConstraints::create();
        if (!$targetNode->getSiteNodeName()->equals($currentRequestSiteDetectionResult->siteNodeName)) {
            /** @var Site $site */
            foreach ($this->siteRepository->findOnline() as $site) {
                if ($site->getNodeName()->equals($targetNode->getSiteNodeName())) {
                    return $this->applyDomainToUriConstraints($uriConstraints, $site->getPrimaryDomain());
                }
            }
        }

        return $uriConstraints;
    }

    private function applyDomainToUriConstraints(UriConstraints $uriConstraints, ?Domain $domain): UriConstraints
    {
        if ($domain === null) {
            return $uriConstraints;
        }
        $uriConstraints = $uriConstraints->withHost($domain->getHostname());
        if (!empty($domain->getScheme())) {
            $uriConstraints = $uriConstraints->withScheme($domain->getScheme());
        }
        if (!empty($domain->getPort())) {
            $uriConstraints = $uriConstraints->withPort($domain->getPort());
        }
        return $uriConstraints;
    }
}
