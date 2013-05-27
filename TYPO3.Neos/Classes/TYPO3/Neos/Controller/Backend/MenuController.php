<?php
namespace TYPO3\Neos\Controller\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class MenuController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 * @Flow\Inject
	 */
	protected $siteRepository;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

	/**
	 * @return string
	 */
	public function indexAction() {
		$this->response->setHeader('Content-Type', 'application/json');

		$contentModuleUri = $this->getControllerContext()->getUriBuilder()
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor('index', array(), 'Backend\Backend', 'TYPO3.Neos');
		return json_encode(array(
			'contentModuleUri' => $contentModuleUri,
			'siteList' => $this->siteList(),
			'moduleList' => $this->moduleList()
		));
	}

	/**
	 * @return array
	 */
	protected function siteList() {
		$siteList = array();
		foreach ($this->siteRepository->findAll() as $site) {

			$siteNode = $this->propertyMapper->convert('/sites/' . $site->getNodeName() . '@user-' . $this->securityContext->getAccount()->getAccountIdentifier(), 'TYPO3\TYPO3CR\Domain\Model\NodeInterface');
			$uri = $this->getControllerContext()->getUriBuilder()
				->reset()
				->setCreateAbsoluteUri(TRUE)
				->uriFor('show', array('node' => $siteNode->getPrimaryChildNode()), 'Frontend\Node', 'TYPO3.Neos');
			$siteList[] = array(
				'name' => $site->getName(),
				'nodeName' => $site->getNodeName(),
				'uri' => $uri
			);
		}
		return $siteList;
	}

	/**
	 * @return array
	 */
	protected function moduleList() {
		$moduleList = array();
		foreach ($this->settings['modules'] as $module => $moduleConfiguration) {
			$submoduleList = array();
			if (isset($moduleConfiguration['submodules'])) {
				foreach ($moduleConfiguration['submodules'] as $submodule => $submoduleConfiguration) {
					$submoduleList[] = $this->collectModuleData($submodule, $submoduleConfiguration, $module . '/' . $submodule);
				}
			}
			$moduleList[] = array_merge(
				$this->collectModuleData($module, $moduleConfiguration, $module),
				array('group' => $module, 'submodules' => $submoduleList)
			);
		}
		return $moduleList;
	}

	/**
	 * @param string $module
	 * @param array $moduleConfiguration
	 * @param string $modulePath
	 * @return array
	 */
	protected function collectModuleData($module, $moduleConfiguration, $modulePath) {
		$moduleUri = $this->getControllerContext()->getUriBuilder()
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor('index', array('module' => $modulePath), 'Backend\Module', 'TYPO3.Neos');
		return array(
			'module' => $module,
			'modulePath' => $modulePath,
			'uri' => $moduleUri,
			'label' => isset($moduleConfiguration['label']) ? $moduleConfiguration['label'] : '',
			'description' => isset($moduleConfiguration['description']) ? $moduleConfiguration['description'] : '',
			'icon' => isset($moduleConfiguration['icon']) ? $moduleConfiguration['icon'] : '',
			'hideInMenu' => isset($moduleConfiguration['hideInMenu']) ? (boolean)$moduleConfiguration['hideInMenu'] : FALSE
		);
	}

}
?>