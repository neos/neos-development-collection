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

namespace Neos\Neos\Domain\Repository;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferenceToWrite;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Service\DomainMatchingStrategy;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * The Site Repository
 *
 * @Flow\Scope("singleton")
 * @api
 */
class DomainRepository
{
    /**
     * @Flow\Inject
     * @var DomainMatchingStrategy
     */
    protected $domainMatchingStrategy;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    public function handleAddNewDomain(Site $site, string $hostname, ?string $scheme, ?int $port): void
    {
        $cr = $this->contentRepositoryRegistry->get($site->siteNodeAggregate->getContentRepositoryId());

        // OriginDimensionSpacePoint::fromDimensionSpacePoint($site->getConfiguration()->defaultDimensionSpacePoint)
        $rootGeneralizations = $cr->getVariationGraph()->getRootGeneralizations();
        $arbitraryOriginDimensionSpacePoint = OriginDimensionSpacePoint::fromDimensionSpacePoint(reset($rootGeneralizations));

        $liveWorkspace = $cr->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());

        try {
            $domainsNodeAggregate = $cr->getContentGraph()->findRootNodeAggregateByType(
                $liveWorkspace->currentContentStreamId,
                NodeTypeNameFactory::forDomains()
            );
        } catch (RootNodeAggregateDoesNotExist) {
            $cr->handle(CreateRootNodeAggregateWithNode::create(
                $liveWorkspace->currentContentStreamId,
                NodeAggregateId::fromString('domains'),
                NodeTypeNameFactory::forDomains()
            ))->block();
            $domainsNodeAggregate = $cr->getContentGraph()->findRootNodeAggregateByType(
                $liveWorkspace->currentContentStreamId,
                NodeTypeNameFactory::forDomains()
            );
        }

        $cr->handle(CreateNodeAggregateWithNode::create(
            $liveWorkspace->currentContentStreamId,
            $domainNodeId = NodeAggregateId::create(),
            NodeTypeNameFactory::forDomain(),
            $arbitraryOriginDimensionSpacePoint,
            $domainsNodeAggregate->nodeAggregateId,
            initialPropertyValues: PropertyValuesToWrite::fromArray(array_filter([
                'hostname' => $hostname,
                'scheme' => $scheme,
                'port' => $port
            ]))
        ))->block();

        // merge with current refs
        $rootGeneralizations = $cr->getVariationGraph()->getRootGeneralizations();
        $arbitraryDimensionSpacePoint = reset($rootGeneralizations);

        $subgraph = $cr->getContentGraph()->getSubgraph(
            $liveWorkspace->currentContentStreamId,
            $arbitraryDimensionSpacePoint,
            VisibilityConstraints::frontend()
        );

        $domainReferenceIds = NodeAggregateIds::fromNodes($subgraph->findReferences(
            $site->siteNodeAggregate->nodeAggregateId,
            FindReferencesFilter::create(
                referenceName: ReferenceName::fromString('domains')
            )
        )->getNodes());

        $cr->handle(SetNodeReferences::create(
            $liveWorkspace->currentContentStreamId,
            $site->siteNodeAggregate->nodeAggregateId,
            $arbitraryOriginDimensionSpacePoint,
            ReferenceName::fromString('domains'),
            NodeReferencesToWrite::fromNodeAggregateIds(
                $domainReferenceIds->merge(
                    NodeAggregateIds::create($domainNodeId)
                )
            )
        ))->block();
    }

    public function handleRemoveDomain(Domain $domain): void
    {
        $cr = $this->contentRepositoryRegistry->get($domain->domainNodeAggregate->getContentRepositoryId());
        $liveWorkspace = $cr->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());

        $cr->handle(RemoveNodeAggregate::create(
            $liveWorkspace->currentContentStreamId,
            $domain->domainNodeAggregate->nodeAggregateId,
            current(iterator_to_array($domain->domainNodeAggregate->coveredDimensionSpacePoints)),
            NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS
        ))->block();
    }

    /**
     * @return array<Domain>
     */
    public function findAll(): array
    {
        $cr = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString('default'));
        $liveWorkspace = $cr->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());

        try {
            $domainsNodeAggregate = $cr->getContentGraph()->findRootNodeAggregateByType(
                $liveWorkspace->currentContentStreamId,
                NodeTypeNameFactory::forDomains()
            );
        } catch (RootNodeAggregateDoesNotExist) {
            return [];
        }

        $domainNodeAggregates = $cr->getContentGraph()->findChildNodeAggregates(
            $liveWorkspace->currentContentStreamId,
            $domainsNodeAggregate->nodeAggregateId
        );

        $legacyDomains = [];

        foreach ($domainNodeAggregates as $domainNodeAggregate) {
            $legacyDomains[] = Domain::fromDomainNodeAggregate($domainNodeAggregate);
        }

        return $legacyDomains;
    }

    /**
     * @return array<Domain>
     */
    public function findByHostname(string $hostname): array
    {
        $matching = [];
        foreach ($this->findAll() as $domain) {
            if ($domain->getHostname() === $hostname) {
                $matching[] = $domain;
            }
        }
        return $matching;
    }

    /**
     * Finds all active domains matching the given hostname.
     *
     * Their order is determined by how well they match, best match first.
     *
     * @param string $hostname Hostname the domain should match with (eg. "localhost" or "www.neos.io")
     * @param boolean $onlyActive Only include active domains
     * @return array<Domain> An array of matching domains
     * @api
     */
    public function findByHost($hostname, $onlyActive = false)
    {
        $domains = $this->findAll();

        return $this->domainMatchingStrategy->getSortedMatches($hostname, $domains);
    }

    /**
     * Find the best matching active domain for the given hostname.
     *
     * @param string $hostname Hostname the domain should match with (eg. "localhost" or "www.neos.io")
     * @param boolean $onlyActive Only include active domains
     * @api
     */
    public function findOneByHost($hostname, $onlyActive = false): ?Domain
    {
        $allMatchingDomains = $this->findByHost($hostname, $onlyActive);
        return count($allMatchingDomains) > 0 ? $allMatchingDomains[0] : null;
    }

    public function findOneByActiveRequest(): ?Domain
    {
        $matchingDomain = null;
        $activeRequestHandler = $this->bootstrap->getActiveRequestHandler();
        if ($activeRequestHandler instanceof HttpRequestHandlerInterface) {
            $matchingDomain = $this->findOneByHost($activeRequestHandler->getHttpRequest()->getUri()->getHost(), true);
        }

        return $matchingDomain;
    }
}
