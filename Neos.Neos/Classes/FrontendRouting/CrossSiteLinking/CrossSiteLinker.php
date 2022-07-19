<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\CrossSiteLinking;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\EventSourcedRouting\ValueObject\DocumentNodeInfo;
use Neos\Neos\FrontendRouting\EventSourcedFrontendNodeRoutePartHandler;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 *  - called from {@see EventSourcedFrontendNodeRoutePartHandler}.
 * You will not call this class yourself.
 *
 * See {@see EventSourcedFrontendNodeRoutePartHandler} for documentation.
 *
 * @Flow\Scope("singleton")
 * @internal
 */
final class CrossSiteLinker
{

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @param DocumentNodeInfo $targetNode the target node where we want to generate the link to
     * @param SiteDetectionResult $currentRequestSiteDetectionResult
     * @return void
     */
    // TODO: API: maybe not $targetNode, but 2x SiteNodeName??
    public function createCrossSiteLink(DocumentNodeInfo $targetNode, SiteDetectionResult $currentRequestSiteDetectionResult): UriConstraints
    {
        $uriConstraints = UriConstraints::create();
        if (!$targetNode->getSiteNodeName()->equals($currentRequestSiteDetectionResult->siteIdentifier->asNodeName())) {
            /** @var Site $site */
            foreach ($this->siteRepository->findOnline() as $site) {
                if ($site->getNodeName() === (string)$targetNode->getSiteNodeName()) {
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
