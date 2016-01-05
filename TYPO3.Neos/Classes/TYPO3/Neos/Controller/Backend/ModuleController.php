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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\MediaTypes;
use TYPO3\Neos\Controller\BackendUserTranslationTrait;
use TYPO3\Neos\Controller\Exception\DisabledModuleException;

/**
 * The TYPO3 Module
 *
 * @Flow\Scope("singleton")
 */
class ModuleController extends \TYPO3\Flow\Mvc\Controller\ActionController
{
    use BackendUserTranslationTrait;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Mvc\Dispatcher
     */
    protected $dispatcher;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Security\Context
     */
    protected $securityContext;

    /**
     * @var \TYPO3\Neos\Controller\Backend\MenuHelper
     * @Flow\Inject
     */
    protected $menuHelper;

    /**
     * @param array $module
     * @return mixed
     */
    public function indexAction(array $module)
    {
        $moduleRequest = new ActionRequest($this->request);
        $moduleRequest->setArgumentNamespace('moduleArguments');
        $moduleRequest->setControllerObjectName($module['controller']);
        $moduleRequest->setControllerActionName($module['action']);
        if (isset($module['format'])) {
            $moduleRequest->setFormat($module['format']);
        }
        if ($this->request->hasArgument($moduleRequest->getArgumentNamespace()) === true && is_array($this->request->getArgument($moduleRequest->getArgumentNamespace()))) {
            $moduleRequest->setArguments($this->request->getArgument($moduleRequest->getArgumentNamespace()));
        }
        foreach ($this->request->getPluginArguments() as $argumentNamespace => $argument) {
            $moduleRequest->setArgument('--' . $argumentNamespace, $argument);
        }

        $modules = explode('/', $module['module']);

        $moduleConfiguration = Arrays::getValueByPath($this->settings['modules'], implode('.submodules.', $modules));
        $moduleConfiguration['path'] = $module['module'];

        if (!$this->menuHelper->isModuleEnabled($moduleConfiguration['path'])) {
            throw new DisabledModuleException(sprintf('The module "%s" is disabled. You can enable it with the "enabled" flag in Settings.yaml.', $module['module']), 1437148922);
        }

        $moduleBreadcrumb = array();
        $path = array();
        foreach ($modules as $moduleIdentifier) {
            array_push($path, $moduleIdentifier);
            $config = Arrays::getValueByPath($this->settings['modules'], implode('.submodules.', $path));
            $moduleBreadcrumb[implode('/', $path)] = $config;
        }

        $moduleRequest->setArgument('__moduleConfiguration', $moduleConfiguration);

        $moduleResponse = new Response($this->response);

        $this->dispatcher->dispatch($moduleRequest, $moduleResponse);

        if ($moduleResponse->hasHeader('Location')) {
            $this->redirectToUri($moduleResponse->getHeader('Location'), 0, $moduleResponse->getStatusCode());
        } elseif ($moduleRequest->getFormat() !== 'html') {
            $mediaType = MediaTypes::getMediaTypeFromFilename('file.' . $moduleRequest->getFormat());
            if ($mediaType !== 'application/octet-stream') {
                $this->controllerContext->getResponse()->setHeader('Content-Type', $mediaType);
            }
            return $moduleResponse->getContent();
        } else {
            $user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');

            $sites = $this->menuHelper->buildSiteList($this->controllerContext);

            $this->view->assignMultiple(array(
                'moduleClass' => implode('-', $modules),
                'moduleContents' => $moduleResponse->getContent(),
                'title' => $moduleRequest->hasArgument('title') ? $moduleRequest->getArgument('title') : $moduleConfiguration['label'],
                'rootModule' => array_shift($modules),
                'submodule' => array_shift($modules),
                'moduleConfiguration' => $moduleConfiguration,
                'moduleBreadcrumb' => $moduleBreadcrumb,
                'user' => $user,
                'modules' => $this->menuHelper->buildModuleList($this->controllerContext),
                'sites' => $sites
            ));
        }
    }
}
