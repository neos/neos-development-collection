<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Ui;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Service\UserService;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class BackendRedirectionServiceAspect
{

    /**
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;


    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;


    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;


    /**
     * @Flow\Inject
     * @var ContentDimensionZookeeper
     */
    protected $contentDimensionZookeeper;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos", path="userInterface.routeAfterLogin.values")
     * @var array
     */
    protected $routingValuesAfterLogin;


    /**
     * @Flow\Around("method(Neos\Neos\Service\BackendRedirectionService->getAfterLoginRedirectionUri())")
     * @param JoinPointInterface $joinPoint the join point
     * @return mixed
     */
    public function onGetAfterLoginRedirectionUri(JoinPointInterface $joinPoint)
    {
        /* @var \Neos\Flow\Mvc\ActionRequest $actionRequest */
        $actionRequest = $joinPoint->getMethodArgument('actionRequest');

        return $this->getAfterLoginRedirectionUri($actionRequest);
    }

    private function getAfterLoginRedirectionUri(\Neos\Flow\Mvc\ActionRequest $actionRequest)
    {
        $user = $this->userService->getBackendUser();
        if ($user === null) {
            return null;
        }


        $account = $this->securityContext->getAccount();
        $workspaceName = WorkspaceName::fromAccountIdentifier($account->getAccountIdentifier());

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);
        $uriBuilder->setFormat('html');
        $uriBuilder->setCreateAbsoluteUri(true);

        $nodeAddressToEdit = $this->getLastVisitedNodeAddress($workspaceName);
        if ($nodeAddressToEdit === null) {
            $nodeAddressToEdit = $this->findCurrentSiteNodeAddress($workspaceName);
        }

        $arguments = array_merge(['node' => $nodeAddressToEdit->serializeForUri()], $this->routingValuesAfterLogin);
        return $uriBuilder->uriFor($this->routingValuesAfterLogin['@action'], $arguments, $this->routingValuesAfterLogin['@controller'], $this->routingValuesAfterLogin['@package']);
    }


    protected function getLastVisitedNodeAddress(WorkspaceName $workspaceName): ?NodeAddress
    {
        if (!$this->session->isStarted() || !$this->session->hasKey('lastVisitedNode')) {
            return null;
        }
        try {
            /* @var $lastVisitedNode \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress */
            $lastVisitedNode = $this->propertyMapper->convert($this->session->getData('lastVisitedNode'), NodeAddress::class);
            return $this->nodeAddressFactory->adjustWithWorkspaceName($lastVisitedNode, $workspaceName->toContentRepositoryWorkspaceName());
        } catch (\Exception $exception) {
            return null;
        }
    }

    private function findCurrentSiteNodeAddress(WorkspaceName $workspaceName)
    {
        $currentDomain = $this->domainRepository->findOneByActiveRequest();

        $contentStreamIdentifier = $this->workspaceFinder->findOneByName($workspaceName->toContentRepositoryWorkspaceName())->getCurrentContentStreamIdentifier();


        $site = ($currentDomain !== null ? $currentDomain->getSite() : $this->siteRepository->findFirstOnline());

        $sitesNode = $this->contentGraph->findRootNodeAggregateByType($contentStreamIdentifier, NodeTypeName::fromString('Neos.Neos:Sites'));

        // TODO: how do we figure out which is the "right" Content Dimension to open??
        /* @var $someContentDimension \Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint */
        $someContentDimension = $this->contentDimensionZookeeper->getAllowedDimensionSubspace()->getIterator()->current();
        $subgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $someContentDimension, VisibilityConstraints::withoutRestrictions());

        $siteNode = $subgraph->findChildNodeConnectedThroughEdgeName(
            $sitesNode->getIdentifier(),
            NodeName::fromString($site->getNodeName())
        );
        return $this->nodeAddressFactory->createFromTraversableNode(new TraversableNode($siteNode, $subgraph));
    }
}
