<?php
namespace TYPO3\Neos\TypoScript;

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
use TYPO3\TypoScript\TypoScriptObjects\CollectionImplementation;

/**
 * TypoScript object for specific content collections, which also renders a
 * "create-new-content" button when not being in live workspace.
 */
class DefaultContentCollectionImplementation extends CollectionImplementation {

	/**
	 * The name of the content collection node which shall be rendered.
	 *
	 * @var string
	 */
	protected $nodePath;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * Sets the identifier of the content collection node which shall be rendered
	 *
	 * @param string $nodePath
	 * @return void
	 */
	public function setNodePath($nodePath) {
		$this->nodePath = $nodePath;
	}

	/**
	 * Returns the identifier of the content collection node which shall be rendered
	 *
	 * @return string
	 */
	public function getNodePath() {
		return $this->tsValue('nodePath');
	}

	/**
	 * Render the list of nodes, and if there are none and we are not inside the live
	 * workspace, render a button to create new content.
	 *
	 * @return string
	 * @throws \TYPO3\Neos\Exception
	 */
	public function evaluate() {
		$currentContext = $this->tsRuntime->getCurrentContext();
		$node = $currentContext['node'];
		$output = parent::evaluate();
		try {
			$this->accessDecisionManager->decideOnResource('TYPO3_Neos_Backend_BackendController');
		} catch (\TYPO3\Flow\Security\Exception\AccessDeniedException $e) {
			return $output;
		}

		if ($node->getNodeType()->isOfType('TYPO3.Neos:ContentCollection')) {
			$contentCollectionNode = $node;
		} else {
			$contentCollectionNode = $node->getNode($this->getNodePath());

			if ($contentCollectionNode === NULL && $this->nodeRepository->getContext()->getWorkspaceName() !== 'live') {
					/**
					 * In case the user created a new page, this page does not have the necessary content collections created yet.
					 * The problem is that we only know during TypoScript rendering which content collections we expect to have
					 * on a certain page; as it is only stored in the "nodePath" property of this ContentCollection TypoScript object.
					 *
					 * Thus, as a workaround, we create new content collection nodes as we need them during rendering, although we
					 * know it is ugly.
					 */
				$contentCollectionNode = $node->createNode($this->getNodePath(), $this->nodeTypeManager->getNodeType('TYPO3.Neos:ContentCollection'));
				$this->nodeRepository->persistEntities();
			}
		}

		if ($contentCollectionNode === NULL) {
				// It might still happen that there is no content collection node on the page,
				// f.e. when we are in live workspace. In this case, we just silently
				// return what we have so far.
			return $output;
		}

		$idAttribute = $this->generateIdAttributeForContentCollection($contentCollectionNode);
		return sprintf('<div about="%s" id="%s" typeof="typo3:%s" rel="typo3:content-collection" class="t3-contentcollection t3-reloadable-content"><script type="text/x-typo3" property="typo3:_typoscriptPath">%s</script><script type="text/x-typo3" property="typo3:__workspacename">%s</script>%s</div>', $contentCollectionNode->getContextPath(), $idAttribute, 'TYPO3.Neos:ContentCollection', $this->path, $contentCollectionNode->getWorkspace()->getName(), $output);
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $contentCollectionNode
	 * @return string
	 */
	protected function generateIdAttributeForContentCollection(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $contentCollectionNode) {
		$parentNode = $this->deriveParentFolderNode($contentCollectionNode);
		$parentFolderNodePath = $parentNode->getPath();
		$relativePath = substr($contentCollectionNode->getPath(), strlen($parentFolderNodePath));
		return 't3-section' . str_replace('/', '-', $relativePath);
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $contentCollectionNode
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 */
	protected function deriveParentFolderNode(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $contentCollectionNode) {
		$parentNode = $contentCollectionNode->getParent();

		while ($parentNode->getNodeType()->isOfType('TYPO3.Neos:Document') !== TRUE) {
			$parentNode = $parentNode->getParent();
		}

		return $parentNode;
	}
}
?>