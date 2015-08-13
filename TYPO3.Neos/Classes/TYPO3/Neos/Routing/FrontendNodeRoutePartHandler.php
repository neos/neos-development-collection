<?php
namespace TYPO3\Neos\Routing;

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
use TYPO3\Flow\Mvc\Routing\DynamicRoutePart;
use TYPO3\Flow\Validation\Validator\UuidValidator;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 */
class FrontendNodeRoutePartHandler extends DynamicRoutePart {

	/**
	 * @Flow\Inject
	 * @var ContentContextFactory
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * Matches a frontend URI pointing to a node (for example a page).

	 * This function tries to find a matching node by the given relative context node path. If one was found, its
	 * absolute context node path is returned in $this->value.

	 * Note that this matcher does not check if access to the resolved workspace or node is allowed because at the point
	 * in time the route part handler is invoked, the security framework is not yet fully initialized.

	 *
	 * @param string $requestPath The relative context node path (without leading "/", relative to the current Site Node)
	 * @return boolean TRUE if the $requestPath could be matched, otherwise FALSE
	 * @throws Exception\NoHomepageException if no node could be found on the homepage (empty $requestPath)
	 */
	protected function matchValue($requestPath) {
		try {
			$node = $this->convertNodeContextPathToNode($requestPath);
		} catch (Exception $exception) {
			if ($requestPath === '') {
				throw new Exception\NoHomepageException('Homepage could not be loaded. Probably you haven\'t imported a site yet', 1346950755, $exception);
			}
			return FALSE;
		}
		if ($this->onlyMatchSiteNodes() && $node !== $node->getContext()->getCurrentSiteNode()) {
			return FALSE;
		}
		if (!$node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			return FALSE;
		}

		if ($node->getContext()->getWorkspace(FALSE)->getName() === 'live') {
			$this->value = $node->getIdentifier();
		} else {
			$this->value = $node->getContextPath();
		}
		return TRUE;
	}

	/**
	 * Extracts the node path from the request path.
	 *
	 * @param string $requestPath The request path to be matched
	 * @return string value to match, or an empty string if $requestPath is empty or split string was not found
	 */
	protected function findValueToMatch($requestPath) {
		if ($this->splitString !== '') {
			$splitStringPosition = strpos($requestPath, $this->splitString);
			if ($splitStringPosition !== FALSE) {
				return substr($requestPath, 0, $splitStringPosition);
			}
		}
		return $requestPath;
	}

	/**
	 * Checks, whether given value is a Node object and if so, sets $this->value to the respective node context path.
	 *
	 * In order to render a suitable frontend URI, this function strips off the path to the site node and only keeps
	 * the actual node path relative to that site node. In practice this function would set $this->value as follows:
	 *
	 * absolute node path: /sites/neostypo3org/homepage/about
	 * $this->value:       homepage/about
	 *
	 * absolute node path: /sites/neostypo3org/homepage/about@user-admin
	 * $this->value:       homepage/about@user-admin
	 *
	 * @param mixed $node Either a Node object or an absolute context node path
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 */
	protected function resolveValue($node) {
		if (!$node instanceof NodeInterface && !is_string($node)) {
			return FALSE;
		}

		if (is_string($node)) {
			if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $node) === 1) {
				$contentContext = $this->buildContextFromWorkspaceName('live');
				try {
					$node = $this->convertNodeIdentifierToNode($node);
				} catch (Exception $exception) {
					return FALSE;
				}
			} else {
				$contentContext = $this->buildContextFromNodeContextPath($node);
				if ($contentContext->getWorkspace(FALSE) === NULL) {
					return FALSE;
				}
				$node = $contentContext->getNode($this->convertNodeContextPathToNodePath($node));
			}
			if ($node === NULL) {
				return FALSE;
			}
		} else {
			$contentContext = $node->getContext();
		}

		if (!$node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			return FALSE;
		}

		$nodeContextPath = $node->getContextPath();
		$siteNode = $contentContext->getCurrentSiteNode();
		$siteNodePath = $siteNode->getPath();
		if ($this->onlyMatchSiteNodes() && $node !== $siteNode) {
			return FALSE;
		}

		if ($nodeContextPath === $siteNodePath) {
			$this->value = '';
		} else {
			$this->value = ltrim(substr($nodeContextPath, strlen($siteNodePath)), '/');
		}
		return TRUE;
	}

	/**
	 * Converts the given $nodeIdentifier to the corresponding node instance, or throws an exception if that fails
	 *
	 * @param string $nodeIdentifier
	 * @return NodeInterface
	 * @throws Exception
	 */
	protected function convertNodeIdentifierToNode($nodeIdentifier) {
		/** @var $liveWorkspace Workspace */
		$liveWorkspace = $this->workspaceRepository->findOneByName('live');
		if ($liveWorkspace === NULL) {
			throw new Exception\NoWorkspaceException('"live" workspace could not be fetched.', 1382617454);
		}
		/** @var $nodeData NodeData */
		$nodeData = $this->nodeDataRepository->findOneByIdentifier($nodeIdentifier, $liveWorkspace);
		if ($nodeData === NULL) {
			throw new Exception\NoSuchNodeException(sprintf('No node found in live workspace with identifier "%s".', $nodeIdentifier), 1382114820);
		}
		$node = $this->nodeFactory->createFromNodeData($nodeData, $this->buildContextFromWorkspaceName('live'));
		if ($node === NULL) {
			throw new Exception\NoSuchNodeException(sprintf('Node "%s" could not be created from node data.', $nodeIdentifier), 1382114825);
		}
		return $node;
	}

	/**
	 * Returns the initialized node that is referenced by $relativeNodeContextPath
	 *
	 * @param string $nodeContextPath The node context path, for example the/node/path@some-workspace
	 * @return NodeInterface
	 * @throws \TYPO3\Neos\Routing\Exception\NoWorkspaceException
	 * @throws \TYPO3\Neos\Routing\Exception\NoSiteException
	 * @throws \TYPO3\Neos\Routing\Exception\NoSuchNodeException
	 * @throws \TYPO3\Neos\Routing\Exception\NoSiteNodeException
	 * @throws \TYPO3\Neos\Routing\Exception\InvalidRequestPathException
	 */
	protected function convertNodeContextPathToNode($nodeContextPath) {
		$relativeNodePath = $this->convertNodeContextPathToNodePath($nodeContextPath);
		if ($relativeNodePath === NULL) {
			throw new Exception\NoSuchNodeException(sprintf('No node found on request path "%s"', $nodeContextPath), 1392726936);
		}
		$contentContext = $this->buildContextFromNodeContextPath($nodeContextPath);
		$workspace = $contentContext->getWorkspace(FALSE);
		if ($workspace === NULL) {
			throw new Exception\NoWorkspaceException(sprintf('No workspace found for request path "%s"', $nodeContextPath), 1346949318);
		}

		$site = $contentContext->getCurrentSite();
		if ($site === NULL) {
			throw new Exception\NoSiteException(sprintf('No site found for request path "%s"', $nodeContextPath), 1346949693);
		}

		$siteNode = $contentContext->getCurrentSiteNode();
		if ($siteNode === NULL) {
			throw new Exception\NoSiteNodeException(sprintf('No site node found for request path "%s"', $nodeContextPath), 1346949728);
		}

		$node = ($relativeNodePath === '') ? $siteNode : $siteNode->getNode($relativeNodePath);
		if (!$node instanceof NodeInterface) {
			throw new Exception\NoSuchNodeException(sprintf('No node found on request path "%s"', $nodeContextPath), 1346949857);
		}

		return $node;
	}

	/**
	 * @param string $nodeContextPath
	 * @return ContentContext
	 * @throws Exception\InvalidRequestPathException
	 */
	protected function buildContextFromNodeContextPath($nodeContextPath) {
		$contextPathParts = array();
		if ($nodeContextPath !== '' && strpos($nodeContextPath, '@') !== FALSE) {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $nodeContextPath, $contextPathParts);
		}
		$workspaceName = isset($contextPathParts['WorkspaceName']) ? $contextPathParts['WorkspaceName'] : 'live';
		return $this->buildContextFromWorkspaceName($workspaceName);
	}

	/**
	 * @param string $workspaceName
	 * @return ContentContext
	 */
	protected function buildContextFromWorkspaceName($workspaceName) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		);

		$currentDomain = $this->domainRepository->findOneByActiveRequest();

		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findOnline()->getFirst();
		}

		return $this->contextFactory->create($contextProperties);
	}

	/**
	 * Returns the node path for a given $nodeContextPath, for example "/sites/somesite/the/node/path"
	 * This could also be a relative path like "the/node/path"
	 *
	 * @param string $nodeContextPath the node context path, for example "/sites/somesite/the/node/path@some-workspace"
	 * @return string
	 */
	protected function convertNodeContextPathToNodePath($nodeContextPath) {
		if ($nodeContextPath === '' || strpos($nodeContextPath, '@') === FALSE) {
			return $nodeContextPath;
		}
		preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $nodeContextPath, $contextPathParts);
		if (isset($contextPathParts['NodePath'])) {
			return $contextPathParts['NodePath'];
		}
		return NULL;
	}

	/**
	 * Whether the current route part should only match/resolve site nodes (e.g. the homepage)
	 *
	 * @return boolean
	 */
	protected function onlyMatchSiteNodes() {
		return isset($this->options['onlyMatchSiteNodes']) && $this->options['onlyMatchSiteNodes'] === TRUE;
	}
}
