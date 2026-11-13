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

use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Controller\Backend\MenuHelper;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Utility\Arrays;

#[Flow\Scope('singleton')]
class BackendRedirectionService
{
    /**
     * @var SessionInterface
     */
    #[Flow\Inject]
    protected $session;

    /**
     * @var DomainRepository
     */
    #[Flow\Inject]
    protected $domainRepository;

    /**
     * @var SiteRepository
     */
    #[Flow\Inject]
    protected $siteRepository;

    /**
     * @var UserService
     */
    #[Flow\Inject]
    protected $userService;

    /**
     * @var PersistenceManagerInterface
     */
    #[Flow\Inject]
    protected $persistenceManager;

    /**
     * @var PropertyMapper
     */
    #[Flow\Inject]
    protected $propertyMapper;

    /**
     * @var MenuHelper
     */
    #[Flow\Inject]
    protected $menuHelper;

    /**
     * @var PrivilegeManagerInterface
     */
    #[Flow\Inject]
    protected $privilegeManager;

    /**
     * @var string[]
     */
    #[Flow\InjectConfiguration(package: 'Neos.Neos', path: 'moduleConfiguration.preferredStartModules')]
    protected $preferedStartModules;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Returns a specific URI string to redirect to after the login; or NULL if there is none.
     *
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
}
