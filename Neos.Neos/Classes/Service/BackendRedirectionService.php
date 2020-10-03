<?php
declare(strict_types=1);

namespace Neos\Neos\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

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
     * @Flow\InjectConfiguration(package="Neos.Neos", path="userInterface.routeAfterLogin.values")
     * @var bool
     */
    protected $routingValuesAfterLogin;

    /**
     * Returns a specific URI string to redirect to after the login; or NULL if there is none.
     *
     * @param ActionRequest $actionRequest
     * @return string
     * @throws \Neos\Flow\Http\Exception
     * @throws MissingActionNameException
     * @throws IllegalObjectTypeException
     */
    public function getAfterLoginRedirectionUri(ActionRequest $actionRequest): ?string
    {
        $user = $this->userService->getBackendUser();
        if ($user === null) {
            return null;
        }

        $workspaceName = $this->userService->getPersonalWorkspaceName();
        $this->createWorkspaceAndRootNodeIfNecessary($workspaceName);

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);
        $uriBuilder->setFormat('html');
        $uriBuilder->setCreateAbsoluteUri(true);

        $nodeToEdit = $this->getLastVisitedNode($workspaceName);
        if ($nodeToEdit === null) {
            $contentContext = $this->createContext($workspaceName);
            $nodeToEdit = $contentContext->getCurrentSiteNode();
        }

        $arguments = array_merge(['node' => $nodeToEdit], $this->routingValuesAfterLogin);
        return $uriBuilder->uriFor($this->routingValuesAfterLogin['@action'], $arguments, $this->routingValuesAfterLogin['@controller'], $this->routingValuesAfterLogin['@package']);
    }

    /**
     * Returns a specific URI string to redirect to after the logout; or NULL if there is none.
     * In case of NULL, it's the responsibility of the AuthenticationController where to redirect,
     * most likely to the LoginController's index action.
     *
     * @param ActionRequest $actionRequest
     * @return string A possible redirection URI, if any
     * @throws \Neos\Flow\Http\Exception
     * @throws MissingActionNameException
     */
    public function getAfterLogoutRedirectionUri(ActionRequest $actionRequest): ?string
    {
        $lastVisitedNode = $this->getLastVisitedNode('live');
        if ($lastVisitedNode === null) {
            return null;
        }
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);
        $uriBuilder->setFormat('html');
        $uriBuilder->setCreateAbsoluteUri(true);
        return $uriBuilder->uriFor('show', ['node' => $lastVisitedNode], 'Frontend\\Node', 'Neos.Neos');
    }

    /**
     * @param string $workspaceName
     * @return NodeInterface
     */
    protected function getLastVisitedNode(string $workspaceName): ?NodeInterface
    {
        if (!$this->session->isStarted() || !$this->session->hasKey('lastVisitedNode')) {
            return null;
        }
        try {
            $lastVisitedNode = $this->propertyMapper->convert($this->session->getData('lastVisitedNode'), NodeInterface::class);
            $q = new FlowQuery([$lastVisitedNode]);
            return $q->context(['workspaceName' => $workspaceName])->get(0);
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
    protected function createContext(string $workspaceName): ContentContext
    {
        $contextProperties = [
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ];

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
     * @throws IllegalObjectTypeException
     */
    protected function createWorkspaceAndRootNodeIfNecessary(string $workspaceName): void
    {
        $workspace = $this->workspaceRepository->findOneByName($workspaceName);
        if ($workspace === null) {
            $liveWorkspace = $this->workspaceRepository->findOneByName('live');
            $owner = $this->userService->getBackendUser();
            $workspace = new Workspace($workspaceName, $liveWorkspace, $owner);
            $this->workspaceRepository->add($workspace);
            $this->persistenceManager->allowObject($workspace);
        }

        $contentContext = $this->createContext($workspaceName);
        $rootNode = $contentContext->getRootNode();
        $this->persistenceManager->allowObject($rootNode);
        $this->persistenceManager->allowObject($rootNode->getNodeData());
        $this->persistenceManager->persistAll(true);
    }
}
