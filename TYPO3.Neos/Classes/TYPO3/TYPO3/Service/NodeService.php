<?php
namespace TYPO3\TYPO3\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3\Domain\Service\ContentContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3\Routing\Exception as Exception;

/**
 * The node service adds some basic helper methods for retrieving nodes from the TYPO3CR
 * This is used by the FrontendNodeRoutePartHandler in order to fetch the currently requested page node
 *
 * @Flow\Scope("singleton")
 */
class NodeService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Returns the initialized node that is referenced by $relativeContextNodePath
	 *
	 * @param string $relativeContextNodePath
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 * @throws \TYPO3\TYPO3\Routing\Exception\NoWorkspaceException
	 * @throws \TYPO3\TYPO3\Routing\Exception\NoSiteException
	 * @throws \TYPO3\TYPO3\Routing\Exception\NoSuchNodeException
	 * @throws \TYPO3\TYPO3\Routing\Exception\NoSiteNodeException
	 * @throws \TYPO3\TYPO3\Routing\Exception\InvalidRequestPathException
	 */
	public function getNodeByContextNodePath($relativeContextNodePath) {
		if ($relativeContextNodePath !== '') {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $relativeContextNodePath, $matches);
			if (!isset($matches['NodePath'])) {
				throw new Exception\InvalidRequestPathException('The request path "' . $relativeContextNodePath . '" is not valid', 1346949309);
			}
			$relativeNodePath = $matches['NodePath'];
		} else {
			$relativeNodePath = '';
		}

		if ($this->nodeRepository->getContext() === NULL) {
			$workspaceName = (isset($matches['WorkspaceName']) ? $matches['WorkspaceName'] : 'live');
			$contentContext = new ContentContext($workspaceName);
			$contentContext->setInvisibleContentShown(TRUE);
			$this->nodeRepository->setContext($contentContext);
		} else {
			$contentContext = $this->nodeRepository->getContext();
		}

		$workspace = $contentContext->getWorkspace(FALSE);
		if (!$workspace) {
			throw new Exception\NoWorkspaceException('No workspace found for request path "' . $relativeContextNodePath . '"', 1346949318);
		}

		$site = $contentContext->getCurrentSite();
		if (!$site) {
			throw new Exception\NoSiteException('No site found for request path "' . $relativeContextNodePath . '"', 1346949693);
		}

		$siteNode = $contentContext->getCurrentSiteNode();
		if (!$siteNode) {
			throw new Exception\NoSiteNodeException('No site node found for request path "' . $relativeContextNodePath . '"', 1346949728);
		}

		$currentAccessModeFromContext = $contentContext->isInaccessibleContentShown();
		$contentContext->setInaccessibleContentShown(TRUE);
		$node = ($relativeNodePath === '') ? $siteNode->getPrimaryChildNode() : $siteNode->getNode($relativeNodePath);
		$contentContext->setInaccessibleContentShown($currentAccessModeFromContext);

		if (!$node instanceof NodeInterface) {
			throw new Exception\NoSuchNodeException('No node found on request path "' . $relativeContextNodePath . '"', 1346949857);
		}
		return $node;
	}

}
?>