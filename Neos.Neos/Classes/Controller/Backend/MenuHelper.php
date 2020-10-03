<?php
namespace Neos\Neos\Controller\Backend;

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
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Security\Authorization\Privilege\ModulePrivilege;
use Neos\Neos\Security\Authorization\Privilege\ModulePrivilegeSubject;
use Neos\Neos\Service\IconNameMappingService;
use Neos\Utility\Arrays;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * A helper class for menu generation in backend controllers / view helpers
 *
 * @Flow\Scope("singleton")
 */
class MenuHelper
{
    /**
     * @var SiteRepository
     * @Flow\Inject
     */
    protected $siteRepository;

    /**
     * @var PrivilegeManagerInterface
     * @Flow\Inject
     */
    protected $privilegeManager;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var IconNameMappingService
     */
    protected $iconMapper;

    /**
     * @param array $settings
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Build a list of sites
     *
     * @param ControllerContext $controllerContext
     * @return array
     */
    public function buildSiteList(ControllerContext $controllerContext)
    {
        $requestUriHost = $controllerContext->getRequest()->getHttpRequest()->getUri()->getHost();

        $domainsFound = false;
        $sites = [];
        foreach ($this->siteRepository->findOnline() as $site) {
            $uri = null;
            $active = false;
            /** @var $site Site */
            if ($site->hasActiveDomains()) {
                $activeHostPatterns = $site->getActiveDomains()->map(function ($domain) {
                    return $domain->getHostname();
                })->toArray();
                $active = in_array($requestUriHost, $activeHostPatterns, true);
                if ($active) {
                    $uri = $controllerContext->getUriBuilder()
                        ->reset()
                        ->setCreateAbsoluteUri(true)
                        ->uriFor('index', [], 'Backend\Backend', 'Neos.Neos');
                } else {
                    $uri = $controllerContext->getUriBuilder()
                        ->reset()
                        ->uriFor('switchSite', ['site' => $site], 'Backend\Backend', 'Neos.Neos');
                }
                $domainsFound = true;
            }

            $sites[] = [
                'name' => $site->getName(),
                'nodeName' => $site->getNodeName(),
                'uri' => $uri,
                'active' => $active
            ];
        }

        if ($domainsFound === false) {
            $uri = $controllerContext->getUriBuilder()
                ->reset()
                ->setCreateAbsoluteUri(true)
                ->uriFor('index', [], 'Backend\Backend', 'Neos.Neos');
            $sites[0]['uri'] = $uri;
        }

        return $sites;
    }

    /**
     * @param ControllerContext $controllerContext
     * @return array
     */
    public function buildModuleList(ControllerContext $controllerContext): array
    {
        $modules = [];
        foreach ($this->settings['modules'] as $moduleName => $moduleConfiguration) {
            if (!$this->isModuleEnabled($moduleName)) {
                continue;
            }
            if (!$this->privilegeManager->isGranted(ModulePrivilege::class, new ModulePrivilegeSubject($moduleName))) {
                continue;
            }
            // @deprecated since Neos 3.2, use the ModulePrivilegeTarget instead!
            if (isset($moduleConfiguration['privilegeTarget']) && !$this->privilegeManager->isPrivilegeTargetGranted($moduleConfiguration['privilegeTarget'])) {
                continue;
            }
            $submodules = [];
            if (isset($moduleConfiguration['submodules'])) {
                foreach ($moduleConfiguration['submodules'] as $submoduleName => $submoduleConfiguration) {
                    $modulePath = $moduleName . '/' . $submoduleName;
                    if (!$this->isModuleEnabled($modulePath)) {
                        continue;
                    }
                    if (!$this->privilegeManager->isGranted(ModulePrivilege::class, new ModulePrivilegeSubject($modulePath))) {
                        continue;
                    }
                    // @deprecated since Neos 3.2, use the ModulePrivilegeTarget instead!
                    if (isset($submoduleConfiguration['privilegeTarget']) && !$this->privilegeManager->isPrivilegeTargetGranted($submoduleConfiguration['privilegeTarget'])) {
                        continue;
                    }
                    $submodules[$submoduleName] = $this->collectModuleData($controllerContext, $submoduleName, $submoduleConfiguration, $moduleName . '/' . $submoduleName);
                }
            }
            $modules[$moduleName] = array_merge(
                $this->collectModuleData($controllerContext, $moduleName, $moduleConfiguration, $moduleName),
                ['group' => $moduleName, 'submodules' => $submodules]
            );
        }

        return $modules;
    }

    /**
     * Checks whether a module is enabled or disabled in the configuration
     *
     * @param string $modulePath name of the module including parent modules ("mainModule/subModule/subSubModule")
     * @return boolean true if module is enabled (default), false otherwise
     */
    public function isModuleEnabled($modulePath)
    {
        $modulePathSegments = explode('/', $modulePath);
        $moduleConfiguration = Arrays::getValueByPath($this->settings['modules'], implode('.submodules.', $modulePathSegments));
        if (isset($moduleConfiguration['enabled']) && $moduleConfiguration['enabled'] !== true) {
            return false;
        }
        array_pop($modulePathSegments);
        if ($modulePathSegments === []) {
            return true;
        }
        return $this->isModuleEnabled(implode('/', $modulePathSegments));
    }

    /**
     * @param ControllerContext $controllerContext
     * @param string $module
     * @param array $moduleConfiguration
     * @param string $modulePath
     * @return array
     */
    protected function collectModuleData(ControllerContext $controllerContext, $module, $moduleConfiguration, $modulePath)
    {
        $moduleUri = $controllerContext->getUriBuilder()
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->uriFor('index', ['module' => $modulePath], 'Backend\Module', 'Neos.Neos');

        $icon = isset($moduleConfiguration['icon']) ? $this->iconMapper->convert($moduleConfiguration['icon']) : '';
        return [
            'module' => $module,
            'modulePath' => $modulePath,
            'uri' => $moduleUri,
            'label' => isset($moduleConfiguration['label']) ? $moduleConfiguration['label'] : '',
            'description' => isset($moduleConfiguration['description']) ? $moduleConfiguration['description'] : '',
            'icon' => $icon,
            'hideInMenu' => isset($moduleConfiguration['hideInMenu']) ? (boolean)$moduleConfiguration['hideInMenu'] : false
        ];
    }
}
