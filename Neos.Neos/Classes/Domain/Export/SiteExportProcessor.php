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

namespace Neos\Neos\Domain\Export;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * Export processor exports Neos {@see Site} instances as json
 *
 * @phpstan-type DomainShape array{hostname: string, scheme?: ?string, port?: ?int, active?: ?bool, primary?: ?bool }
 * @phpstan-type SiteShape array{name:string, siteResourcesPackageKey:string, nodeName: string, online:bool, domains: DomainShape[] }
 *
 */
final readonly class SiteExportProcessor implements ProcessorInterface
{
    public function __construct(
        private ContentRepository $contentRepository,
        private WorkspaceName $workspaceName,
        private SiteRepository $siteRepository,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $sites = $this->getSiteData();
        $context->files->write(
            'sites.json',
            json_encode($sites, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * @return SiteShape[]
     */
    private function getSiteData(): array
    {
        $sites = $this->findSites($this->workspaceName);
        $siteData = [];
        foreach ($sites as $site) {
            $siteData[] = [
                "name" => $site->getName(),
                "nodeName" => $site->getNodeName()->value,
                "siteResourcesPackageKey" => $site->getSiteResourcesPackageKey(),
                "online" => $site->isOnline(),
                "domains" => array_map(
                    fn(Domain $domain) => [
                        'hostname' => $domain->getHostname(),
                        'scheme' => $domain->getScheme(),
                        'port' => $domain->getPort(),
                        'active' => $domain->getActive(),
                        'primary' => $domain === $site->getPrimaryDomain(fallbackToActive: false),
                    ],
                    $site->getDomains()->toArray()
                )
            ];
        }

        return $siteData;
    }

    /**
     * @param WorkspaceName $workspaceName
     * @return Site[]
     */
    private function findSites(WorkspaceName $workspaceName): array
    {
        $contentGraph = $this->contentRepository->getContentGraph($workspaceName);
        $sitesNodeAggregate = $contentGraph->findRootNodeAggregateByType(NodeTypeNameFactory::forSites());
        if ($sitesNodeAggregate === null) {
            return [];
        }

        $siteNodeAggregates = $contentGraph->findChildNodeAggregates($sitesNodeAggregate->nodeAggregateId);
        $sites = [];
        foreach ($siteNodeAggregates as $siteNodeAggregate) {
            $siteNodeName = $siteNodeAggregate->nodeName?->value;
            if ($siteNodeName === null) {
                continue;
            }
            $site = $this->siteRepository->findOneByNodeName($siteNodeName);
            if ($site === null) {
                continue;
            }
            $sites[] = $site;
        }
        return $sites;
    }
}
