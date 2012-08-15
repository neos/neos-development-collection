<?php
namespace TYPO3\TYPO3\Controller\Module\Management;

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

/**
 * The TYPO3 Workspaces module controller
 *
 * @FLOW3\Scope("singleton")
 */
class WorkspacesController extends \TYPO3\TYPO3\Controller\Module\StandardController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Service\WorkspacesService
	 */
	protected $workspacesService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Property\PropertyMappingConfigurationBuilder
	 */
	protected $propertyMappingConfigurationBuilder;

	/**
	 * @return void
	 */
	protected function initializeAction() {
		if ($this->arguments->hasArgument('node')) {
			$this->arguments->getArgument('node')->getPropertyMappingConfiguration()->setTypeConverterOption('TYPO3\TYPO3\Routing\NodeObjectConverter', \TYPO3\TYPO3\Routing\NodeObjectConverter::REMOVED_CONTENT_SHOWN, TRUE);
		}
	}

	/**
	 * @param string $workspaceName
	 * @return void
	 * @todo Pagination
	 * @todo Tree filtering + level limit
	 * @todo Search field
	 * @todo Difference mechanism
	 */
	public function indexAction($workspaceName = NULL) {
		if (is_null($workspaceName)) {
			$workspaceName = $this->securityContext->getParty()->getPreferences()->get('context.workspace');
		}
		$contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext($workspaceName);
		$contentContext->setInvisibleContentShown(TRUE);
		$contentContext->setRemovedContentShown(TRUE);
		$contentContext->setInaccessibleContentShown(TRUE);
		$this->nodeRepository->setContext($contentContext);

		$contentTypes = $this->contentTypeManager->getSubContentTypes('TYPO3.TYPO3:AbstractNode');

		$sites = array();
		foreach ($this->workspacesService->getUnpublishedNodes($workspaceName) as $node) {
			$pathParts = explode('/', $node->getPath());
			if (count($pathParts) > 2) {
				$siteNodeName = $pathParts[2];
				$folder = $this->findFolderNode($node);
				$folderPath = implode('/', array_slice(explode('/', $folder->getPath()), 3));
				$relativePath = str_replace(sprintf('/sites/%s/%s', $siteNodeName, $folderPath), '', $node->getPath());
				if (!isset($sites[$siteNodeName]['siteNode'])) {
					$sites[$siteNodeName]['siteNode'] = $this->siteRepository->findOneByNodeName($siteNodeName);
				}
				$sites[$siteNodeName]['folders'][$folderPath]['folderNode'] = $folder;
				$change = array('node' => $node);
				if (isset($contentTypes[$node->getContentType()])) {
					$change['configuration'] = $contentTypes[$node->getContentType()]->getConfiguration();
				}
				$sites[$siteNodeName]['folders'][$folderPath]['changes'][$relativePath] = $change;
			}
		}

		$liveWorkspace = $this->workspacesService->getWorkspace('live');

		ksort($sites);
		foreach ($sites as $siteKey => $site) {
			foreach ($site['folders'] as $folderKey => $folder) {
				foreach ($folder['changes'] as $changeKey => $change) {
					$liveNode = $this->getSiblingNodeInWorkspace($change['node'], $liveWorkspace);
					$sites[$siteKey]['folders'][$folderKey]['changes'][$changeKey]['isNew'] = is_null($liveNode);
					$sites[$siteKey]['folders'][$folderKey]['changes'][$changeKey]['isMoved'] = $liveNode && $change['node']->getParentPath() !== $liveNode->getParentPath();
				}
			}
			ksort($sites[$siteKey]['folders']);
		}

		$workspaces = array();
		foreach ($this->workspacesService->getWorkspaces() as $workspace) {
			array_push($workspaces, array(
				'workspaceNode' => $workspace,
				'unpublishedNodesCount' => $this->workspacesService->getUnpublishedNodesCount($workspace->getName())
			));
		}

		$this->view->assignMultiple(array(
			'workspaceName' => $workspaceName,
			'workspaces' => $workspaces,
			'sites' => $sites
		));
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function publishNodeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->workspacesService->publishNode($node);
		$this->flashMessageContainer->addMessage(new \TYPO3\FLOW3\Error\Message('Node has been published'));
		$this->redirect('index');
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function discardNodeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->nodeRepository->remove($node);
		$this->flashMessageContainer->addMessage(new \TYPO3\FLOW3\Error\Message('Node has been discarded'));
		$this->redirect('index');
	}

	/**
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
	 * @param string $action
	 * @return void
	 */
	public function publishOrDiscardNodesAction(array $nodes, $action) {
		$propertyMappingConfiguration = $this->propertyMappingConfigurationBuilder->build();
		$propertyMappingConfiguration->setTypeConverterOption('TYPO3\TYPO3\Routing\NodeObjectConverter', \TYPO3\TYPO3\Routing\NodeObjectConverter::REMOVED_CONTENT_SHOWN, TRUE);
		foreach ($nodes as $key => $node) {
			$nodes[$key] = $this->propertyMapper->convert($node, 'TYPO3\TYPO3CR\Domain\Model\NodeInterface', $propertyMappingConfiguration);
		}
		if ($action === 'publish') {
			foreach ($nodes as $node) {
				$this->workspacesService->publishNode($node);
			}
			$message = 'Selected changes have been published';
		} elseif ($action === 'discard') {
			foreach ($nodes as $node) {
				$this->nodeRepository->remove($node);
			}
			$message = 'Selected changes have been discarded';
		}
		$this->flashMessageContainer->addMessage(new \TYPO3\FLOW3\Error\Message($message));
		$this->redirect('index');
	}

	/**
	 * @param string $workspaceName
	 * @return void
	 */
	public function publishWorkspaceAction($workspaceName) {
		$this->workspacesService->getWorkspace($workspaceName)->publish('live');
		$this->flashMessageContainer->addMessage(new \TYPO3\FLOW3\Error\Message('Changes in workspace "%s" have been published', NULL, array($workspaceName)));
		$this->redirect('index');
	}

	/**
	 * @param string $workspaceName
	 * @return void
	 */
	public function discardWorkspaceAction($workspaceName) {
		foreach ($this->workspacesService->getUnpublishedNodes($workspaceName) as $node) {
			if ($node->getPath() !== '/') {
				$this->nodeRepository->remove($node);
			}
		}
		$this->flashMessageContainer->addMessage(new \TYPO3\FLOW3\Error\Message('Changes in workspace "%s" have been discarded', NULL, array($workspaceName)));
		$this->redirect('index');
	}

	/**
	 * Finds the nearest parent folder node of the provided node by looping recursively trough
	 * the node and it's parent nodes and checking if they are a sub content type of TYPO3.TYPO3CR:Folder
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface|NULL
	 */
	protected function findFolderNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$folderTypes = $this->contentTypeManager->getSubContentTypes('TYPO3.TYPO3CR:Folder');
		while ($node) {
			if (array_key_exists($node->getContentType(), $folderTypes)) {
				return $node;
			}
			$node = $node->getParent();
		}
		return NULL;
	}

	/**
	 * Gets the sibling node in a different workspace connected via the identifier'
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface|NULL
	 */
	protected function getSiblingNodeInWorkspace(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$query = $this->nodeRepository->createQuery();
		return $query->matching(
			$query->logicalAnd(
				$query->equals('workspace', $workspace),
				$query->equals('identifier', $node->getIdentifier()
			)
		))->execute()->getFirst();
	}

}
?>