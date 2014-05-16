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
use TYPO3\Neos\Routing\Exception\NoSuchLanguageException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 */
class FrontendNodeRoutePartHandler extends DynamicRoutePart implements FrontendNodeRoutePartHandlerInterface {

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
	 * Matches a frontend URI pointing to a node (for example a page).
	 *
	 * This function tries to find a matching node by the given relative context node path. If one was found, its
	 * absolute context node path is returned in $this->value.
	 *
	 * Note that this matcher does not check if access to the resolved workspace or node is allowed because at the point
	 * in time the route part handler is invoked, the security framework is not yet fully initialized.
	 *
	 * @param string $requestPath The relative context node path (without leading "/", relative to the current Site Node)
	 * @return boolean TRUE if the $requestPath could be matched, otherwise FALSE
	 * @throws \Exception
	 * @throws Exception\NoSuchLanguageException
	 * @throws Exception\NoHomepageException if no node could be found on the homepage (empty $requestPath)
	 */
	protected function matchValue($requestPath) {
		try {
			$node = $this->convertRequestPathToNode($requestPath);
		} catch (NoSuchLanguageException $exception) {
			throw $exception;
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

		$this->value = $node->getContextPath();

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
			$nodeContextPath = $node;
			$contentContext = $this->buildContextFromContextPath($nodeContextPath);
			if ($contentContext->getWorkspace(FALSE) === NULL) {
				return FALSE;
			}
			$node = $contentContext->getNode($this->removeContextFromContextPath($nodeContextPath));

			if ($node === NULL) {
				return FALSE;
			}
		} else {
			$contentContext = $node->getContext();
		}

		if (!$node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			return FALSE;
		}

		$siteNode = $contentContext->getCurrentSiteNode();
		if ($this->onlyMatchSiteNodes() && $node !== $siteNode) {
			return FALSE;
		}

		$routePath = $this->resolveRoutePathForNode($siteNode, $node);
		$this->value = $routePath;
		return TRUE;
	}

	/**
	 * Returns the initialized node that is referenced by $relativeNodeContextPath
	 *
	 * @param string $requestPath The node context path, for example /sites/foo/the/node/path@some-workspace
	 * @return NodeInterface
	 * @throws \TYPO3\Neos\Routing\Exception\NoWorkspaceException
	 * @throws \TYPO3\Neos\Routing\Exception\NoSiteException
	 * @throws \TYPO3\Neos\Routing\Exception\NoSuchNodeException
	 * @throws \TYPO3\Neos\Routing\Exception\NoSiteNodeException
	 * @throws \TYPO3\Neos\Routing\Exception\InvalidRequestPathException
	 */
	protected function convertRequestPathToNode($requestPath) {
		$relativeNodePath = $this->removeContextFromRequestPath($requestPath);
		if ($relativeNodePath === NULL) {
			throw new Exception\NoSuchNodeException(sprintf('No node found on request path "%s"', $requestPath), 1392726936);
		}
		$contentContext = $this->buildContextFromRequestPath($requestPath);
		$workspace = $contentContext->getWorkspace(FALSE);
		if ($workspace === NULL) {
			throw new Exception\NoWorkspaceException(sprintf('No workspace found for request path "%s"', $requestPath), 1346949318);
		}

		$site = $contentContext->getCurrentSite();
		if ($site === NULL) {
			throw new Exception\NoSiteException(sprintf('No site found for request path "%s"', $requestPath), 1346949693);
		}

		$siteNode = $contentContext->getCurrentSiteNode();
		if ($siteNode === NULL) {
			throw new Exception\NoSiteNodeException(sprintf('No site node found for request path "%s"', $requestPath), 1346949728);
		}

		$node = ($relativeNodePath === '') ? $siteNode : $siteNode->getNode($relativeNodePath);
		if (!$node instanceof NodeInterface) {
			throw new Exception\NoSuchNodeException(sprintf('No node found on request path "%s"', $requestPath), 1346949857);
		}

		return $node;
	}

	/**
	 * @param $contextPath
	 * @return ContentContext
	 */
	protected function buildContextFromContextPath($contextPath) {
		return $this->buildContextFromPath($contextPath, TRUE);
	}

	/**
	 * @param $requestPath
	 * @return ContentContext
	 */
	protected function buildContextFromRequestPath($requestPath) {
		return $this->buildContextFromPath($requestPath, FALSE);
	}

	/**
	 * @param string $path a path containing the context, such as foo/bar/@user-blubb
	 * @param boolean $convertLiveDimensions Whether to parse dimensions from the context path in a non-live workspace
	 * @return ContentContext adhering to the path; only evaluating the context information (e.g. everything after "@")
	 * @throws Exception\InvalidRequestPathException
	 */
	protected function buildContextFromPath($path, $convertLiveDimensions) {
		$contextPathParts = array();
		if ($path !== '' && strpos($path, '@') !== FALSE) {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $path, $contextPathParts);
		}
		$workspaceName = isset($contextPathParts['WorkspaceName']) && $contextPathParts['WorkspaceName'] !== '' ? $contextPathParts['WorkspaceName'] : 'live';

		$dimensions = NULL;
		if (($workspaceName !== 'live' || $convertLiveDimensions === TRUE) && isset($contextPathParts['Dimensions'])) {
			$dimensions = $this->contextFactory->parseDimensionValueStringToArray($contextPathParts['Dimensions']);
		}

		return $this->buildContextFromWorkspaceName($workspaceName, $dimensions);
	}

	/**
	 * @param string $workspaceName
	 * @param array $dimensions
	 * @return ContentContext
	 */
	protected function buildContextFromWorkspaceName($workspaceName, array $dimensions = NULL) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		);

		if ($dimensions !== NULL) {
			$contextProperties['dimensions'] = $dimensions;
		}

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
	 * @param string $nodeContextPath
	 * @return string
	 */
	protected function removeContextFromContextPath($nodeContextPath) {
		return $this->removeContextFromPath($nodeContextPath);
	}

	/**
	 * @param string $requestPath
	 * @return string
	 */
	protected function removeContextFromRequestPath($requestPath) {
		return $this->removeContextFromPath($requestPath);
	}

	/**
	 * @param string $path an absolute or relative node path which possibly contains context information, for example "/sites/somesite/the/node/path@some-workspace"
	 * @return string the same path without context information
	 */
	protected function removeContextFromPath($path) {
		if ($path === '' || strpos($path, '@') === FALSE) {
			return $path;
		}
		preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $path, $contextPathParts);
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

	/**
	 * @param NodeInterface $siteNode
	 * @param NodeInterface $node
	 * @return string the route path (URI part) for the passed $node
	 */
	protected function resolveRoutePathForNode($siteNode, $node) {
		$workspaceName = $node->getContext()->getWorkspaceName();
		if ($workspaceName !== 'live') {
			// we directly take the node's context path here; such that all dimensions are encoded inside.
			$nodeContextPath = $node->getContextPath();
		} else {
			$nodeContextPath = $node->getPath();
		}

		$siteNodePath = $siteNode->getPath();

		if ($nodeContextPath === $siteNodePath) {
			return '';
		} else {
			return ltrim(substr($nodeContextPath, strlen($siteNodePath)), '/');
		}
	}
}
