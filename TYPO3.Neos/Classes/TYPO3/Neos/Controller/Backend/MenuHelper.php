<?php
namespace TYPO3\Neos\Controller\Backend;

/*
 * This file is part of the TYPO3.Neos package.
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
use Neos\Flow\Utility\Arrays;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Repository\SiteRepository;

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
        $sites = array();
        foreach ($this->siteRepository->findOnline() as $site) {
            $uri = null;
            $active = false;
            /** @var $site Site */
            if ($site->hasActiveDomains()) {
                $activeHostPatterns = $site->getActiveDomains()->map(function ($domain) {
                    return $domain->getHostPattern();
                })->toArray();
                $active = in_array($requestUriHost, $activeHostPatterns, true);
                if ($active) {
                    $uri = $controllerContext->getUriBuilder()
                        ->reset()
                        ->setCreateAbsoluteUri(true)
                        ->uriFor('index', array(), 'Backend\Backend', 'TYPO3.Neos');
                } else {
                    $uri = $controllerContext->getUriBuilder()
                        ->reset()
                        ->uriFor('switchSite', array('site' => $site), 'Backend\Backend', 'TYPO3.Neos');
                }
                $domainsFound = true;
            }

            $sites[] = array(
                'name' => $site->getName(),
                'nodeName' => $site->getNodeName(),
                'uri' => $uri,
                'active' => $active
            );
        }

        if ($domainsFound === false) {
            $uri = $controllerContext->getUriBuilder()
                ->reset()
                ->setCreateAbsoluteUri(true)
                ->uriFor('index', array(), 'Backend\Backend', 'TYPO3.Neos');
            $sites[0]['uri'] = $uri;
        }

        return $sites;
    }

    /**
     * @param ControllerContext $controllerContext
     * @return array
     */
    public function buildModuleList(ControllerContext $controllerContext)
    {
        $modules = array();
        foreach ($this->settings['modules'] as $module => $moduleConfiguration) {
            if (!$this->isModuleEnabled($module)) {
                continue;
            }
            if (isset($moduleConfiguration['privilegeTarget']) && !$this->privilegeManager->isPrivilegeTargetGranted($moduleConfiguration['privilegeTarget'])) {
                continue;
            }
            $submodules = array();
            if (isset($moduleConfiguration['submodules'])) {
                foreach ($moduleConfiguration['submodules'] as $submodule => $submoduleConfiguration) {
                    if (!$this->isModuleEnabled($module . '/' . $submodule)) {
                        continue;
                    }
                    if (isset($submoduleConfiguration['privilegeTarget']) && !$this->privilegeManager->isPrivilegeTargetGranted($submoduleConfiguration['privilegeTarget'])) {
                        continue;
                    }
                    $submodules[] = $this->collectModuleData($controllerContext, $submodule, $submoduleConfiguration, $module . '/' . $submodule);
                }
            }
            $modules[] = array_merge(
                $this->collectModuleData($controllerContext, $module, $moduleConfiguration, $module),
                array('group' => $module, 'submodules' => $submodules)
            );
        }
        return $modules;
    }

    /**
     * Checks whether a module is enabled or disabled in the configuration
     *
     * @param string $modulePath name of the module including parent modules ("mainModule/subModule/subSubModule")
     * @return boolean TRUE if module is enabled (default), FALSE otherwise
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
            ->uriFor('index', array('module' => $modulePath), 'Backend\Module', 'TYPO3.Neos');
        return array(
            'module' => $module,
            'modulePath' => $modulePath,
            'uri' => $moduleUri,
            'label' => isset($moduleConfiguration['label']) ? $moduleConfiguration['label'] : '',
            'description' => isset($moduleConfiguration['description']) ? $moduleConfiguration['description'] : '',
            'icon' => isset($moduleConfiguration['icon']) ? $moduleConfiguration['icon'] : '',
            'hideInMenu' => isset($moduleConfiguration['hideInMenu']) ? (boolean)$moduleConfiguration['hideInMenu'] : false
        );
    }
}
