<?php
namespace TYPO3\Neos\Controller\Module\Management;

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
 * The TYPO3 Workspaces module controller
 *
 * @Flow\Scope("singleton")
 */
class WorkspacesController extends \TYPO3\Neos\Controller\Module\StandardController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\WorkspacesService
	 */
	protected $workspacesService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMappingConfigurationBuilder
	 */
	protected $propertyMappingConfigurationBuilder;

	/**
	 * @return void
	 */
	protected function initializeAction() {
		if ($this->arguments->hasArgument('node')) {
			$this->arguments->getArgument('node')->getPropertyMappingConfiguration()->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', \TYPO3\TYPO3CR\TypeConverter\NodeConverter::REMOVED_CONTENT_SHOWN, TRUE);
		}
		parent::initializeAction();
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
			$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
			$workspaceName = $user->getPreferences()->get('context.workspace');
		}
		$contentContext = new \TYPO3\Neos\Domain\Service\ContentContext($workspaceName);
		$contentContext->setInvisibleContentShown(TRUE);
		$contentContext->setRemovedContentShown(TRUE);
		$contentContext->setInaccessibleContentShown(TRUE);
		$this->nodeRepository->setContext($contentContext);

		$sites = array();
		foreach ($this->workspacesService->getUnpublishedNodes($workspaceName) as $node) {
			if (!$node->getContentType()->isOfType('TYPO3.Neos.ContentTypes:Section')) {
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
					if ($node->getContentType()->isOfType('TYPO3.Neos.ContentTypes:AbstractNode')) {
						$change['configuration'] = $node->getContentType()->getConfiguration();
					}
					$sites[$siteNodeName]['folders'][$folderPath]['changes'][$relativePath] = $change;
				}
			}
		}

		$liveWorkspace = $this->workspacesService->getWorkspace('live');

		ksort($sites);
		foreach ($sites as $siteKey => $site) {
			foreach ($site['folders'] as $folderKey => $folder) {
				foreach ($folder['changes'] as $changeKey => $change) {
					$liveNode = $this->nodeRepository->findOneByIdentifier($change['node']->getIdentifier(), $liveWorkspace);
					$sites[$siteKey]['folders'][$folderKey]['changes'][$changeKey]['isNew'] = is_null($liveNode);
					$sites[$siteKey]['folders'][$folderKey]['changes'][$changeKey]['isMoved'] = $liveNode && $change['node']->getPath() !== $liveNode->getPath();
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return void
	 */
	public function publishNodeAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		$this->workspacesService->publishNode($node);
		$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Message('Node has been published'));
		$this->redirect('index');
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return void
	 */
	public function discardNodeAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		$this->nodeRepository->remove($node);
		$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Message('Node has been discarded'));
		$this->redirect('index');
	}

	/**
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface> $nodes
	 * @param string $action
	 * @return void
	 */
	public function publishOrDiscardNodesAction(array $nodes, $action) {
		$propertyMappingConfiguration = $this->propertyMappingConfigurationBuilder->build();
		$propertyMappingConfiguration->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', \TYPO3\TYPO3CR\TypeConverter\NodeConverter::REMOVED_CONTENT_SHOWN, TRUE);
		foreach ($nodes as $key => $node) {
			$nodes[$key] = $this->propertyMapper->convert($node, 'TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface', $propertyMappingConfiguration);
		}
		switch ($action) {
			case 'publish':
				foreach ($nodes as $node) {
					$this->workspacesService->publishNode($node);
				}
				$message = 'Selected changes have been published';
			break;
			case 'discard':
				foreach ($nodes as $node) {
					$this->nodeRepository->remove($node);
				}
				$message = 'Selected changes have been discarded';
			break;
			default:
				throw new \RuntimeException('Invalid action "' . $action . '" given.', 1346167441);
		}

		$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Message($message));
		$this->redirect('index');
	}

	/**
	 * @param string $workspaceName
	 * @return void
	 */
	public function publishWorkspaceAction($workspaceName) {
		$this->workspacesService->getWorkspace($workspaceName)->publish('live');
		$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Message('Changes in workspace "%s" have been published', NULL, array($workspaceName)));
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
		$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Message('Changes in workspace "%s" have been discarded', NULL, array($workspaceName)));
		$this->redirect('index');
	}

	/**
	 * Finds the nearest parent folder node of the provided node by looping recursively trough
	 * the node and it's parent nodes and checking if they are a sub content type of TYPO3.TYPO3CR:Folder
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 */
	protected function findFolderNode(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		while ($node) {
			if ($node->getContentType()->isOfType('TYPO3.TYPO3CR:Folder')) {
				return $node;
			}
			$node = $node->getParent();
		}
		return NULL;
	}

}
?>