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
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\Routing\DynamicRoutePart;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\Neos\Routing\Exception\InvalidRequestPathException;
use TYPO3\Neos\Routing\Exception\NoSuchDimensionValueException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 */
class FrontendNodeRoutePartHandler extends DynamicRoutePart implements FrontendNodeRoutePartHandlerInterface {

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

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
	 * @var ContentDimensionPresetSourceInterface
	 */
	protected $contentDimensionPresetSource;

	const DIMENSION_REQUEST_PATH_MATCHER = '|^
		(?<dimensionPresetUriSegments>[^/@]+)      # the first part of the URI, before the first slash is the encoded dimension preset
		(?:                                        # start of non-capturing submatch for the remaining URL
			/?                                     # a "/"; optional. it must also match en@user-admin
			(?<remainingRequestPath>.*)            # the remaining request path
		)?                                         # ... and this whole remaining URL is optional
		$                                          # make sure we consume the full string
	|x';

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
	 * Matches a frontend URI pointing to a node (for example a page).
	 *
	 * This function tries to find a matching node by the given request path. If one was found, its
	 * absolute context node path is set in $this->value and TRUE is returned.
	 *
	 * Note that this matcher does not check if access to the resolved workspace or node is allowed because at the point
	 * in time the route part handler is invoked, the security framework is not yet fully initialized.
	 *
	 * @param string $requestPath The request path (without leading "/", relative to the current Site Node)
	 * @return boolean TRUE if the $requestPath could be matched, otherwise FALSE
	 * @throws \Exception
	 * @throws Exception\NoHomepageException if no node could be found on the homepage (empty $requestPath)
	 */
	protected function matchValue($requestPath) {
		try {
			$node = $this->convertRequestPathToNode($requestPath);
		} catch (Exception $exception) {
			if ($requestPath === '') {
				throw new Exception\NoHomepageException('Homepage could not be loaded. Probably you haven\'t imported a site yet', 1346950755, $exception);
			}

			$this->systemLogger->log('FrontendNodeRoutePartHandler matchValue(): ' . $exception->getMessage(), LOG_DEBUG);

			return FALSE;
		}
		if ($this->onlyMatchSiteNodes() && $node !== $node->getContext()->getCurrentSiteNode()) {
			return FALSE;
		}

		$this->value = $node->getContextPath();

		return TRUE;
	}

	/**
	 * Returns the initialized node that is referenced by $requestPath, based on the node's
	 * "uriPathSegment" property.
	 *
	 * Note that $requestPath will be modified (passed by reference) by buildContextFromRequestPath().
	 *
	 * @param string $requestPath The request path, for example /the/node/path@some-workspace
	 * @return NodeInterface
	 * @throws \TYPO3\Neos\Routing\Exception\NoWorkspaceException
	 * @throws \TYPO3\Neos\Routing\Exception\NoSiteException
	 * @throws \TYPO3\Neos\Routing\Exception\NoSuchNodeException
	 * @throws \TYPO3\Neos\Routing\Exception\NoSiteNodeException
	 * @throws \TYPO3\Neos\Routing\Exception\InvalidRequestPathException
	 */
	protected function convertRequestPathToNode($requestPath) {
		$contentContext = $this->buildContextFromRequestPath($requestPath);
		$requestPathWithoutContext = $this->removeContextFromPath($requestPath);

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

		if ($requestPathWithoutContext === '') {
			$node = $siteNode;
		} else {
			$relativeNodePath = $this->getRelativeNodePathByUriPathSegmentProperties($siteNode, $requestPathWithoutContext);
			$node = ($relativeNodePath !== FALSE) ? $siteNode->getNode($relativeNodePath) : NULL;
		}

		if (!$node instanceof NodeInterface) {
			throw new Exception\NoSuchNodeException(sprintf('No node found on request path "%s"', $requestPath), 1346949857);
		}

		return $node;
	}

	/**
	 * Checks, whether given value is a Node object and if so, sets $this->value to the respective node path.
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
			$nodePath = $this->removeContextFromPath($nodeContextPath);
			$node = $contentContext->getNode($nodePath);

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
	 * @param string $contextPath
	 * @return ContentContext
	 */
	protected function buildContextFromContextPath($contextPath) {
		return $this->buildContextFromPath($contextPath, TRUE);
	}

	/**
	 * Creates a content context from the given request path, considering possibly mentioned content dimension values.
	 *
	 * @param string &$requestPath The request path. If at least one content dimension is configured, the first path segment will identify the content dimension values
	 * @return ContentContext The built content context
	 */
	protected function buildContextFromRequestPath(&$requestPath) {
		$dimensionsAndDimensionValues = $this->parseDimensionsAndNodePathFromRequestPath($requestPath);

		$contextPathParts = array();
		if ($requestPath !== '' && strpos($requestPath, '@') !== FALSE) {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $requestPath, $contextPathParts);
		}
		$workspaceName = isset($contextPathParts['WorkspaceName']) && $contextPathParts['WorkspaceName'] !== '' ? $contextPathParts['WorkspaceName'] : 'live';

		return $this->buildContextFromWorkspaceNameAndDimensions($workspaceName, $dimensionsAndDimensionValues);
	}

	/**
	 * Creates a content context from the given "context path", i.e. a string used for _resolving_ (not matching) a node.
	 *
	 * @param string $path a path containing the context, such as /sites/examplecom/home@user-johndoe or /assets/pictures/my-picture or /assets/pictures/my-picture@user-john;language=de&country=global
	 * @param boolean $convertLiveDimensions Whether to parse dimensions from the context path in a non-live workspace
	 * @return ContentContext based on the specified path; only evaluating the context information (i.e. everything after "@")
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
			$contextProperties['currentSite'] = $this->siteRepository->findFirstOnline();
		}

		return $this->contextFactory->create($contextProperties);
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
	 * Resolves the request path, also known as route path, identifying the given node.
	 *
	 * A path is built, based on the uri path segment properties of the parents of and the given node itself.
	 * If content dimensions are configured, the first path segment will the identifiers of the dimension
	 * values according to the current context.
	 *
	 * @param NodeInterface $siteNode The site node, used as a starting point while traversing the tree
	 * @param NodeInterface $node The node where the generated path should lead to
	 * @return string The relative route path, possibly prefixed with a segment for identifying the current content dimension values
	 */
	protected function resolveRoutePathForNode(NodeInterface $siteNode, NodeInterface $node) {
		$workspaceName = $node->getContext()->getWorkspaceName();

		$nodeContextPath = $node->getContextPath();
		$nodeContextPathSuffix = ($workspaceName !== 'live') ? substr($nodeContextPath, strpos($nodeContextPath, '@')) : '';

		$currentNodeIsSiteNode = ($siteNode === $node);
		$dimensionsUriSegment = $this->getUriSegmentForDimensions($node->getContext()->getDimensions(), $currentNodeIsSiteNode);
		$requestPath = $this->getRequestPathByNode($siteNode, $node);

		return trim($dimensionsUriSegment . $requestPath, '/') . $nodeContextPathSuffix;
	}

	/**
	 * Builds a node path which matches the given request path.
	 *
	 * This method traverses the segments of the given request path and tries to find nodes on the current level which
	 * have a matching "uriPathSegment" property. If no node could be found which would match the given request path,
	 * FALSE is returned.
	 *
	 * @param NodeInterface $siteNode The site node, used as a starting point while traversing the tree
	 * @param string $relativeRequestPath The request path, relative to the site's root path
	 * @throws \TYPO3\Neos\Routing\Exception\NoSuchNodeException
	 * @return string
	 */
	protected function getRelativeNodePathByUriPathSegmentProperties(NodeInterface $siteNode, $relativeRequestPath) {
		$relativeNodePathSegments = array();
		$node = $siteNode;

		foreach (explode('/', $relativeRequestPath) as $pathSegment) {
			$foundNodeInThisSegment = FALSE;
			foreach ($node->getChildNodes('TYPO3.Neos:Document') as $node) {
				/** @var NodeInterface $node */
				if ($node->getProperty('uriPathSegment') === $pathSegment) {
					$relativeNodePathSegments[] = $node->getName();
					$foundNodeInThisSegment = TRUE;
					break;
				}
			}
			if (!$foundNodeInThisSegment) {
				return FALSE;
			}
		}

		return implode('/', $relativeNodePathSegments);
	}

	/**
	 * Renders a request path based on the "uriPathSegment" properties of the nodes leading to the given node.
	 *
	 * @param NodeInterface $siteNode Top level node, corresponds to the top level of the request path
	 * @param NodeInterface $node The node where the generated path should lead to
	 * @return string A relative request path
	 * @throws Exception\MissingNodePropertyException if the given node doesn't have a "uriPathSegment" property set
	 */
	protected function getRequestPathByNode(NodeInterface $siteNode, NodeInterface $node) {
		if ($siteNode === $node) {
			return '';
		}

		$requestPathSegments = array();
		while ($siteNode !== $node && $node instanceof NodeInterface) {
			if (!$node->hasProperty('uriPathSegment')) {
				throw new Exception\MissingNodePropertyException(sprintf('Missing "uriPathSegment" property for node "%s". Nodes can be migrated with the "flow node:repair" command.', $node->getPath()), 1415020326);
			}

			$pathSegment = $node->getProperty('uriPathSegment');
			$requestPathSegments[] = $pathSegment;
			$node = $node->getParent();
		}

		return implode('/', array_reverse($requestPathSegments));
	}

	/**
	 * Parses the given request path and checks if the first path segment is one or a set of content dimension preset
	 * identifiers. If that is the case, the return value is an array of dimension names and their preset URI segments.
	 *
	 * If the first path segment contained content dimension information, it is removed from &$requestPath.
	 *
	 * @param string &$requestPath The request path currently being processed by this route part handler, e.g. "de_global/startseite/ueber-uns"
	 * @return array An array of dimension name => dimension values (array of string)
	 * @throws InvalidRequestPathException
	 * @throws NoSuchDimensionValueException
	 */
	protected function parseDimensionsAndNodePathFromRequestPath(&$requestPath) {
		$dimensionPresets = $this->contentDimensionPresetSource->getAllPresets();
		if (count($dimensionPresets) === 0) {
			return array();
		}

		$dimensionsAndDimensionValues = array();
		$matches = array();

		preg_match(self::DIMENSION_REQUEST_PATH_MATCHER, $requestPath, $matches);

		if (!isset($matches['dimensionPresetUriSegments'])) {
			foreach ($dimensionPresets as $dimensionName => $dimensionPreset) {
				$dimensionsAndDimensionValues[$dimensionName] = $dimensionPreset['presets'][$dimensionPreset['defaultPreset']]['values'];
			}
		} else {
			$dimensionPresetUriSegments = explode('_', $matches['dimensionPresetUriSegments']);

			if (count($dimensionPresetUriSegments) !== count($dimensionPresets)) {
				throw new InvalidRequestPathException(sprintf('The first path segment of the request URI (%s) does not contain the necessary content dimension preset identifiers for all configured dimensions. This might be an old URI which doesn\'t match the current dimension configuration anymore.', $requestPath), 1413389121);
			}

			foreach ($dimensionPresets as $dimensionName => $dimensionPreset) {
				$uriSegment = array_shift($dimensionPresetUriSegments);
				$preset = $this->contentDimensionPresetSource->findPresetByUriSegment($dimensionName, $uriSegment);
				if ($preset === NULL) {
					throw new NoSuchDimensionValueException(sprintf('Could not find a preset for content dimension "%s" through the given URI segment "%s".', $dimensionName, $uriSegment), 1413389321);
				}
				$dimensionsAndDimensionValues[$dimensionName] = $preset['values'];
			}

			$requestPath = (isset($matches['remainingRequestPath']) ? $matches['remainingRequestPath'] : '');
		}

		return $dimensionsAndDimensionValues;
	}

	/**
	 * Sets context properties like "invisibleContentShown" according to the workspace (live or not) and returns a
	 * ContentContext object.
	 *
	 * @param string $workspaceName Name of the workspace to use in the context
	 * @param array $dimensionsAndDimensionValues An array of dimension names (index) and their values (array of strings). See also: ContextFactory
	 * @return ContentContext
	 */
	protected function buildContextFromWorkspaceNameAndDimensions($workspaceName, array $dimensionsAndDimensionValues) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => ($workspaceName !== 'live'),
			'inaccessibleContentShown' => ($workspaceName !== 'live'),
			'dimensions' => $dimensionsAndDimensionValues
		);

		$currentDomain = $this->domainRepository->findOneByActiveRequest();

		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findFirstOnline();
		}

		return $this->contextFactory->create($contextProperties);
	}

	/**
	 * Find a URI segment in the content dimension presets for the given "language" dimension values
	 *
	 * This will do a reverse lookup from actual dimension values to a preset and fall back to the default preset if none
	 * can be found.
	 *
	 * @param array $dimensionsValues An array of dimensions and their values, indexed by dimension name
	 * @param boolean $currentNodeIsSiteNode If the current node is actually the site node
	 * @return string
	 * @throws \Exception
	 */
	protected function getUriSegmentForDimensions(array $dimensionsValues, $currentNodeIsSiteNode) {
		$uriSegment = '';
		$allDimensionPresetsAreDefault = TRUE;

		foreach ($this->contentDimensionPresetSource->getAllPresets() as $dimensionName => $dimensionPresets) {
			$preset = NULL;
			if (isset($dimensionsValues[$dimensionName])) {
				$preset = $this->contentDimensionPresetSource->findPresetByDimensionValues($dimensionName, $dimensionsValues[$dimensionName]);
			}
			$defaultPreset = $this->contentDimensionPresetSource->getDefaultPreset($dimensionName);
			if ($preset === NULL) {
				$preset = $defaultPreset;
			}
			if ($preset !== $defaultPreset) {
				$allDimensionPresetsAreDefault = FALSE;
			}
			if (!isset($preset['uriSegment'])) {
				throw new \Exception(sprintf('No "uriSegment" configured for content dimension preset "%s" for dimension "%s". Please check the content dimension configuration in Settings.yaml', $preset['identifier'], $dimensionName), 1395824520);
			}
			$uriSegment .= $preset['uriSegment'] . '_';
		}

		if ($allDimensionPresetsAreDefault && $currentNodeIsSiteNode) {
			return '/';
		} else {
			return ltrim(trim($uriSegment, '_') . '/', '/');
		}
	}
}
