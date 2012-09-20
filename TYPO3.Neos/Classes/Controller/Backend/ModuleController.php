<?php
namespace TYPO3\TYPO3\Controller\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Mvc\ActionRequest;
use TYPO3\FLOW3\Http\Response;

/**
 * The TYPO3 Module
 *
 * @FLOW3\Scope("singleton")
 */
class ModuleController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @param array $module
	 * @return void
	 * @FLOW3\SkipCsrfProtection
	 */
	public function indexAction(array $module) {
		$moduleRequest = new ActionRequest($this->request);
		$moduleRequest->setArgumentNamespace('moduleArguments');
		$moduleRequest->setControllerObjectName($module['controller']);
		$moduleRequest->setControllerActionName($module['action']);
		if ($this->request->hasArgument($moduleRequest->getArgumentNamespace()) === TRUE && is_array($this->request->getArgument($moduleRequest->getArgumentNamespace()))) {
			$moduleRequest->setArguments($this->request->getArgument($moduleRequest->getArgumentNamespace()));
		}
		foreach ($this->request->getPluginArguments() as $argumentNamespace => $argument) {
			$moduleRequest->setArgument('--' . $argumentNamespace, $argument);
		}

		$moduleConfiguration = \TYPO3\FLOW3\Utility\Arrays::getValueByPath($this->settings['modules'], implode('.submodules.', explode('/', $module['module'])));
		$moduleConfiguration['path'] = $module['module'];

		$moduleBreadcrumb = array();
		$path = array();
		$modules = explode('/', $module['module']);
		foreach ($modules as $moduleIdentifier) {
			array_push($path, $moduleIdentifier);
			$config = \TYPO3\FLOW3\Utility\Arrays::getValueByPath($this->settings['modules'], implode('.submodules.', $path));
			$moduleBreadcrumb[implode('/', $path)] = $config['label'];
		}

		$moduleRequest->setArgument('__moduleConfiguration', $moduleConfiguration);
		$moduleRequest->setArgument('__moduleBreadcrumb', $moduleBreadcrumb);

		$moduleResponse = new Response($this->response);

		$this->dispatcher->dispatch($moduleRequest, $moduleResponse);

		$this->view->assignMultiple(array(
			'moduleClass' => implode('-', $modules),
			'moduleContents' => $moduleResponse->getContent(),
			'title' => $moduleRequest->hasArgument('title') ? $moduleRequest->getArgument('title') : $moduleConfiguration['label'],
			'rootModule' => array_shift($modules),
			'submodule' => array_shift($modules),
			'moduleConfiguration' => $moduleConfiguration
		));
	}

}
?>