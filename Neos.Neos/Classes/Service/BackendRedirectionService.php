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

namespace Neos\Neos\Service;

use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Controller\Backend\MenuHelper;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Utility\Arrays;

#[Flow\Scope('singleton')]
class BackendRedirectionService
{
    /**
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

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
     * @Flow\Inject
     * @var MenuHelper
     */
    protected $menuHelper;

    /**
     * @var PrivilegeManagerInterface
     * @Flow\Inject
     */
    protected $privilegeManager;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos", path="moduleConfiguration.preferredStartModules")
     * @var string[]
     */
    protected $preferedStartModules;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

    /**
     * Returns a specific URI string to redirect to after the login; or NULL if there is none.
     *
     * @param ControllerContext $controllerContext
     * @return string
     * @throws IllegalObjectTypeException
     * @throws MissingActionNameException
     * @throws \Neos\Flow\Http\Exception
     */
    public function getAfterLoginRedirectionUri(ControllerContext $controllerContext): ?string
    {
        $user = $this->userService->getBackendUser();
        if ($user === null) {
            return null;
        }

        $availableModules = $this->menuHelper->buildModuleList($controllerContext);
        $startModule = $this->determineStartModule($availableModules);

        return $startModule['uri'] ?? null;
    }

    /**
     * @param array<mixed> $availableModules
     * @return array<mixed>|null
     */
    protected function determineStartModule(array $availableModules): ?array
    {
        foreach ($this->preferedStartModules as $startModule) {
            $subModulePath = str_replace('/', '.submodules.', $startModule);
            if (Arrays::getValueByPath($availableModules, $subModulePath) !== null) {
                return Arrays::getValueByPath($availableModules, $subModulePath);
            }
        }

        $firstModule = current($availableModules);
        if (array_key_exists('submodules', $firstModule) && is_array($firstModule['submodules'])) {
            return current($firstModule['submodules']);
        }

        return $firstModule;
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
        $lastVisitedNode = $this->getLastVisitedNode('live', $actionRequest);
        if ($lastVisitedNode === null) {
            return null;
        }
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);
        $uriBuilder->setFormat('html');
        $uriBuilder->setCreateAbsoluteUri(true);

        return $uriBuilder->uriFor('show', ['node' => $lastVisitedNode], 'Frontend\\Node', 'Neos.Neos');
    }

    protected function getLastVisitedNode(string $workspaceName, ActionRequest $actionRequest): ?NodeInterface
    {
        $contentRepositoryIdentifier = SiteDetectionResult::fromRequest($actionRequest->getHttpRequest())->contentRepositoryIdentifier;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspaceName));
        if (!$workspace || !$this->session->isStarted() || !$this->session->hasKey('lastVisitedNode')) {
            return null;
        }
        try {
            /** @var NodeInterface $lastVisitedNode */
            $lastVisitedNode = $this->propertyMapper->convert(
                $this->session->getData('lastVisitedNode'),
                NodeInterface::class
            );

            return $this->nodeAccessorManager->accessorFor(
                new ContentSubgraphIdentity(
                    $contentRepositoryIdentifier,
                    $workspace->getCurrentContentStreamIdentifier(),
                    $lastVisitedNode->getSubgraphIdentity()->dimensionSpacePoint,
                    VisibilityConstraints::withoutRestrictions()
                )
            )->findByIdentifier($lastVisitedNode->getNodeAggregateIdentifier());
        } catch (\Exception $exception) {
            return null;
        }
    }
}
