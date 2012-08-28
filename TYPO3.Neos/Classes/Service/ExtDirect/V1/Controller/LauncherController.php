<?php
namespace TYPO3\TYPO3\Service\ExtDirect\V1\Controller;

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
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * ExtDirect Controller for launcher search
 *
 * @FLOW3\Scope("singleton")
 */
class LauncherController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\ExtJS\ExtDirect\View';

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Domain\Service\NodeSearchService
	 */
	protected $nodeSearchService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Select special error action
	 *
	 * @return void
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
	}

	/**
	 * Returns the specified node
	 *
	 * @param string $term
	 * @param integer $requestIndex
	 * @return void
	 * @ExtDirect
	 * @todo Improve this WIP search implementation
	 */
	public function searchAction($term, $requestIndex) {
		$contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext($this->securityContext->getParty()->getPreferences()->get('context.workspace'));
		$this->nodeRepository->setContext($contentContext);

		$searchContentGroups = array();
		$searchContentTypes = array();
		foreach (array('TYPO3.TYPO3:Page', 'TYPO3.TYPO3:ContentObject') as $contentType) {
			$searchContentGroups[$contentType] = $this->contentTypeManager->getContentType($contentType)->getConfiguration();
			array_push($searchContentTypes, $contentType);
			$subContentTypes = $this->contentTypeManager->getSubContentTypes($contentType);
			if (count($subContentTypes) > 0) {
				$searchContentGroups[$contentType]['subContentTypes'] = $subContentTypes;
				$searchContentTypes = array_merge($searchContentTypes, array_keys($subContentTypes));
			}
		}

		$staticWebBaseUri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/TYPO3.TYPO3/';

		$groups = array();
		foreach ($this->nodeSearchService->findByProperties($term, $searchContentTypes) as $result) {
			$contentType = $result->getContentType();
			if (array_key_exists($contentType->getName(), $searchContentGroups)) {
				$type = $contentType->getName();
			} else {
				foreach ($searchContentGroups as $searchContentGroup => $searchContentGroupConfiguration) {
					if (isset($searchContentGroupConfiguration['subContentTypes']) && array_key_exists($contentType->getName(), $searchContentGroupConfiguration['subContentTypes'])) {
						$type = $searchContentGroup;
						break;
					}
				}
			}
			if (!array_key_exists($type, $groups)) {
				$groups[$type] = array(
					'type' => $contentType->getName(),
					'label' => $searchContentGroups[$type]['search'],
					'items' => array()
				);
			}
			foreach ($contentType->getProperties() as $property => $configuration) {
				if ($property[0] !== '_') {
					$labelProperty = $property;
					break;
				}
			}
			$this->uriBuilder->reset();
			if ($result->getContentType()->isOfType('TYPO3.TYPO3:Page')) {
				$pageNode = $result;
			} else {
				$pageNode = $this->findNextParentFolderNode($result);
				$this->uriBuilder->setSection('c' . $result->getIdentifier());
			}
			$searchResult = array(
				'type' => $contentType->getName(),
				'label' => substr(trim(strip_tags($result->getProperty($labelProperty))), 0, 50),
				'action' => $this->uriBuilder->uriFor('show', array('node' => $pageNode), 'Frontend\Node', 'TYPO3.TYPO3'),
				'path' => $result->getPath()
			);
			$contentTypeConfiguration = $contentType->getConfiguration();
			if (isset($contentTypeConfiguration['darkIcon'])) {
				$searchResult['icon'] = $staticWebBaseUri . $contentTypeConfiguration['darkIcon'];
			}
			array_push($groups[$type]['items'], $searchResult);
		}
		$data = array(
			'requestIndex' => $requestIndex,
			'actions' => array(
				array(
					'label' => 'Clear all cache',
					'command' => 'clear:cache:all'
				),
				array(
					'label' => 'Clear page cache',
					'command' => 'clear:cache:pages'
				)
			),
			'results' => array_values($groups)
		);
		$this->view->assign('value', array('data' => $data, 'success' => TRUE));
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->arguments->getValidationResults());
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected function findNextParentFolderNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		while ($node = $node->getParent()) {
			if ($node->getContentType()->isOfType('TYPO3.TYPO3CR:Folder')) {
				return $node;
			}
		}
		return NULL;
	}

}
?>