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

namespace Neos\Neos\Domain\Pruning;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Severity;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * Pruning processor that removes all Neos {@see Site} instances referenced by the current content repository
 */
final readonly class SitePruningProcessor implements ProcessorInterface
{
    public function __construct(
        private ContentRepository $contentRepository,
        private WorkspaceName $workspaceName,
        private SiteRepository $siteRepository,
        private DomainRepository $domainRepository,
        private PersistenceManagerInterface $persistenceManager
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        try {
            $contentGraph = $this->contentRepository->getContentGraph($this->workspaceName);
        } catch (WorkspaceDoesNotExist) {
            $context->dispatch(Severity::NOTICE, sprintf('Could not find any matching sites, because the workspace "%s" does not exist.', $this->workspaceName->value));
            return;
        }
        $sites = $this->findAllSites($contentGraph);
        foreach ($sites as $site) {
            $domains = $site->getDomains();
            if ($site->getPrimaryDomain() !== null) {
                $site->setPrimaryDomain(null);
                $this->siteRepository->update($site);
            }
            foreach ($domains as $domain) {
                $this->domainRepository->remove($domain);
            }
            $this->persistenceManager->persistAll();
            $this->siteRepository->remove($site);
            $this->persistenceManager->persistAll();
        }
    }

    /**
     * @return Site[]
     */
    protected function findAllSites(ContentGraphInterface $contentGraph): array
    {
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
