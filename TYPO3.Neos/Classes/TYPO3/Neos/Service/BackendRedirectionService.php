<?php
namespace TYPO3\Neos\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Session\SessionInterface;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * @Flow\Scope("singleton")
 */
class BackendRedirectionService
{
    /**
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

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
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * Returns a specific URI string to redirect to after the login; or NULL if there is none.
     *
     * @param ActionRequest $actionRequest
     * @return string
     */
    public function getAfterLoginRedirectionUri(ActionRequest $actionRequest)
    {
        $user = $this->userService->getBackendUser();
        if ($user === null) {
            return null;
        }

        $workspaceName = $this->userService->getUserWorkspaceName();
        $this->createWorkspaceAndRootNodeIfNecessary($workspaceName);

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);
        $uriBuilder->setFormat('html');
        $uriBuilder->setCreateAbsoluteUri(true);

        $contentContext = $this->createContext($workspaceName);
        $lastVisitedNode = $this->getLastVisitedNode($workspaceName);
        if ($lastVisitedNode !== null) {
            return $uriBuilder->uriFor('show', array('node' => $lastVisitedNode), 'Frontend\\Node', 'TYPO3.Neos');
        }

        return $uriBuilder->uriFor('show', array('node' => $contentContext->getCurrentSiteNode()), 'Frontend\\Node', 'TYPO3.Neos');
    }

    /**
     * Returns a specific URI string to redirect to after the logout; or NULL if there is none.
     * In case of NULL, it's the responsibility of the AuthenticationController where to redirect,
     * most likely to the LoginController's index action.
     *
     * @param ActionRequest $actionRequest
     * @return string A possible redirection URI, if any
     */
    public function getAfterLogoutRedirectionUri(ActionRequest $actionRequest)
    {
        $lastVisitedNode = $this->getLastVisitedNode('live');
        if ($lastVisitedNode === null) {
            return null;
        }
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);
        $uriBuilder->setFormat('html');
        $uriBuilder->setCreateAbsoluteUri(true);
        return $uriBuilder->uriFor('show', array('node' => $lastVisitedNode), 'Frontend\\Node', 'TYPO3.Neos');
    }

    /**
     *
     * @param string $workspaceName
     * @return NodeInterface
     */
    protected function getLastVisitedNode($workspaceName)
    {
        if (!$this->session->isStarted() || !$this->session->hasKey('lastVisitedNode')) {
            return null;
        }
        try {
            $lastVisitedNode = $this->propertyMapper->convert($this->session->getData('lastVisitedNode'), NodeInterface::class);
            $q = new FlowQuery([$lastVisitedNode]);
            $lastVisitedNodeUserWorkspace = $q->context(['workspaceName' => $workspaceName])->get(0);
            return $lastVisitedNodeUserWorkspace;
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Create a ContentContext to be used for the backend redirects.
     *
     * @param string $workspaceName
     * @return ContentContext
     */
    protected function createContext($workspaceName)
    {
        $contextProperties = array(
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        );

        $currentDomain = $this->domainRepository->findOneByActiveRequest();
        if ($currentDomain !== null) {
            $contextProperties['currentSite'] = $currentDomain->getSite();
            $contextProperties['currentDomain'] = $currentDomain;
        } else {
            $contextProperties['currentSite'] = $this->siteRepository->findFirstOnline();
        }
        return $this->contextFactory->create($contextProperties);
    }

    /**
     * If the specified workspace or its root node does not exist yet, the workspace and root node will be created.
     *
     * This method is basically a safeguard for legacy and potentially broken websites where users might not have
     * their own workspace yet. In a normal setup, the Domain User Service is responsible for creating and deleting
     * user workspaces.
     *
     * @param string $workspaceName Name of the workspace
     * @return void
     */
    protected function createWorkspaceAndRootNodeIfNecessary($workspaceName)
    {
        $workspace = $this->workspaceRepository->findOneByName($workspaceName);
        if ($workspace === null) {
            $liveWorkspace = $this->workspaceRepository->findOneByName('live');
            $owner = $this->userService->getBackendUser();
            $workspace = new Workspace($workspaceName, $liveWorkspace, $owner);
            $this->workspaceRepository->add($workspace);
            $this->persistenceManager->whitelistObject($workspace);
        }

        $contentContext = $this->createContext($workspaceName);
        $rootNode = $contentContext->getRootNode();
        $this->persistenceManager->whitelistObject($rootNode);
        $this->persistenceManager->whitelistObject($rootNode->getNodeData());
        $this->persistenceManager->persistAll(true);
    }
}
