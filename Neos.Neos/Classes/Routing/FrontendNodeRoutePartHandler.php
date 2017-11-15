<?php
namespace Neos\Neos\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\Routing\DynamicRoutePart;
use Neos\Flow\Security\Context;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Http\DetectContentSubgraphComponent;
use Neos\Neos\Routing\Exception\InvalidDimensionPresetCombinationException;
use Neos\Neos\Routing\Exception\InvalidRequestPathException;
use Neos\Neos\Routing\Exception\NoSuchDimensionValueException;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Utility\NodePaths;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 */
class FrontendNodeRoutePartHandler extends DynamicRoutePart implements FrontendNodeRoutePartHandlerInterface
{

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var ContentContextContainer
     * @todo remove once the content context is passed directly
     */
    protected $contentContextContainer;

    /**
     * @Flow\Inject
     * @var ContentContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

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
     * @Flow\InjectConfiguration("routing.supportEmptySegmentForDimensions")
     * @var boolean
     */
    protected $supportEmptySegmentForDimensions;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    const DIMENSION_REQUEST_PATH_MATCHER = '|^
        (?<firstUriPart>[^/@]+)                    # the first part of the URI, before the first slash, may contain the encoded dimension preset
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
    protected function findValueToMatch($requestPath)
    {
        if ($this->splitString !== '') {
            $splitStringPosition = strpos($requestPath, $this->splitString);
            if ($splitStringPosition !== false) {
                return substr($requestPath, 0, $splitStringPosition);
            }
        }

        return $requestPath;
    }

    /**
     * Matches a frontend URI pointing to a node (for example a page).
     *
     * This function tries to find a matching node by the given request path. If one was found, its
     * absolute context node path is set in $this->value and true is returned.
     *
     * Note that this matcher does not check if access to the resolved workspace or node is allowed because at the point
     * in time the route part handler is invoked, the security framework is not yet fully initialized.
     *
     * @param string $requestPath The request path (without leading "/", relative to the current Site Node)
     * @return boolean true if the $requestPath could be matched, otherwise false
     * @throws \Exception
     * @throws Exception\NoHomepageException if no node could be found on the homepage (empty $requestPath)
     */
    protected function matchValue($requestPath)
    {
        try {
            /** @var NodeInterface $node */
            $node = null;

            // Build context explicitly without authorization checks because the security context isn't available yet
            // anyway and any Entity Privilege targeted on Workspace would fail at this point:
            $this->securityContext->withoutAuthorizationChecks(function () use (&$node, $requestPath) {
                $node = $this->convertRequestPathToNode($requestPath);
            });
        } catch (Exception $exception) {
            $this->systemLogger->log('FrontendNodeRoutePartHandler matchValue(): ' . $exception->getMessage(), LOG_DEBUG);
            if ($requestPath === '') {
                throw new Exception\NoHomepageException('Homepage could not be loaded. Probably you haven\'t imported a site yet', 1346950755, $exception);
            }

            return false;
        }
        if ($this->onlyMatchSiteNodes() && $node !== $node->getContext()->getCurrentSiteNode()) {
            return false;
        }

        $this->value = $node->getContextPath();

        return true;
    }

    /**
     * Returns the initialized node that is referenced by $requestPath, based on the node's
     * "uriPathSegment" property.
     *
     * Note that $requestPath will be modified (passed by reference) by buildContextFromRequestPath().
     *
     * @param string $requestPath The request path, for example /the/node/path@some-workspace
     * @return NodeInterface
     * @throws \Neos\Neos\Routing\Exception\NoWorkspaceException
     * @throws \Neos\Neos\Routing\Exception\NoSiteException
     * @throws \Neos\Neos\Routing\Exception\NoSuchNodeException
     * @throws \Neos\Neos\Routing\Exception\NoSiteNodeException
     * @throws \Neos\Neos\Routing\Exception\InvalidRequestPathException
     */
    protected function convertRequestPathToNode($requestPath)
    {
        $contentContext = $this->contentContextContainer->getContentContext();
        $requestPathWithoutContext = $this->removeContextFromPath($requestPath);

        $workspace = $contentContext->getWorkspace();
        if ($workspace === null) {
            throw new Exception\NoWorkspaceException(sprintf('No workspace found for request path "%s"', $requestPath), 1346949318);
        }

        $site = $contentContext->getCurrentSite();
        if ($site === null) {
            throw new Exception\NoSiteException(sprintf('No site found for request path "%s"', $requestPath), 1346949693);
        }

        $siteNode = $contentContext->getCurrentSiteNode();
        if ($siteNode === null) {
            $currentDomain = $contentContext->getCurrentDomain() ? 'Domain with hostname "' . $contentContext->getCurrentDomain()->getHostname() . '" matched.' : 'No specific domain matched.';
            throw new Exception\NoSiteNodeException(sprintf('No site node found for request path "%s". %s', $requestPath, $currentDomain), 1346949728);
        }

        if ($requestPathWithoutContext === '') {
            $node = $siteNode;
        } else {
            $relativeNodePath = $this->getRelativeNodePathByUriPathSegmentProperties($siteNode, $requestPathWithoutContext);
            $node = ($relativeNodePath !== false) ? $siteNode->getNode($relativeNodePath) : null;
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
     * @return boolean true if value could be resolved successfully, otherwise false.
     */
    protected function resolveValue($node)
    {
        if (!$node instanceof NodeInterface && !is_string($node)) {
            return false;
        }

        if (is_string($node)) {
            \Neos\Flow\var_dump('huhu');
            exit();
            $nodeContextPath = $node;
            $contentContext = $this->buildContextFromPath($nodeContextPath, true);
            if ($contentContext->getWorkspace() === null) {
                return false;
            }
            $nodePath = $this->removeContextFromPath($nodeContextPath);
            $node = $contentContext->getNode($nodePath);

            if ($node === null) {
                return false;
            }
        } else {
            $contentContext = $node->getContext();
        }

        if (!$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            return false;
        }

        $siteNode = $contentContext->getCurrentSiteNode();
        if ($this->onlyMatchSiteNodes() && $node !== $siteNode) {
            return false;
        }

        $routePath = $this->resolveRoutePathForNode($node);
        $this->value = $routePath;

        return true;
    }

    /**
     * Creates a content context from the given "context path", i.e. a string used for _resolving_ (not matching) a node.
     *
     * @param string $path a path containing the context, such as /sites/examplecom/home@user-johndoe or /assets/pictures/my-picture or /assets/pictures/my-picture@user-john;language=de&country=global
     * @param boolean $convertLiveDimensions Whether to parse dimensions from the context path in a non-live workspace
     * @return ContentContext based on the specified path; only evaluating the context information (i.e. everything after "@")
     * @throws Exception\InvalidRequestPathException
     */
    protected function buildContextFromPath($path, $convertLiveDimensions)
    {
        $workspaceName = 'live';
        $dimensions = null;

        if ($path !== '' && NodePaths::isContextPath($path)) {
            $nodePathAndContext = NodePaths::explodeContextPath($path);
            $workspaceName = $nodePathAndContext['workspaceName'];
            $dimensions = ($workspaceName !== 'live' || $convertLiveDimensions === true) ? $nodePathAndContext['dimensions'] : null;
        }

        return $this->buildContextFromWorkspaceName($workspaceName, $dimensions);
    }

    /**
     * @param string $workspaceName
     * @param array $dimensions
     * @return ContentContext
     */
    protected function buildContextFromWorkspaceName($workspaceName, array $dimensions = null)
    {
        $contextProperties = [
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ];

        if ($dimensions !== null) {
            $contextProperties['dimensions'] = $dimensions;
        }

        return $this->contextFactory->create($contextProperties);
    }

    /**
     * @param string $path an absolute or relative node path which possibly contains context information, for example "/sites/somesite/the/node/path@some-workspace"
     * @return string the same path without context information
     */
    protected function removeContextFromPath($path)
    {
        if ($this->contentContextContainer->isUriPathSegmentUsed()) {
            $pivot = mb_strpos($path, '/');
            $path = $pivot === false ? '' : mb_substr($path, $pivot + 1);
        }
        if ($path === '' || NodePaths::isContextPath($path) === false) {
            return $path;
        }
        try {

            if (strpos($path, '@') === 0) {
                $path = '/' . $path;
            }
            $nodePathAndContext = NodePaths::explodeContextPath($path);
            // This is a workaround as we potentially prepend the context path with "/" in buildContextFromRequestPath to create a valid context path,
            // the code in this class expects an empty nodePath though for the site node, so we remove it again at this point.
            return $nodePathAndContext['nodePath'] === '/' ? '' : $nodePathAndContext['nodePath'];
        } catch (\InvalidArgumentException $exception) {
        }

        return null;
    }

    /**
     * Whether the current route part should only match/resolve site nodes (e.g. the homepage)
     *
     * @return boolean
     */
    protected function onlyMatchSiteNodes()
    {
        return isset($this->options['onlyMatchSiteNodes']) && $this->options['onlyMatchSiteNodes'] === true;
    }

    /**
     * Resolves the request path, also known as route path, identifying the given node.
     *
     * A path is built, based on the uri path segment properties of the parents of and the given node itself.
     * If content dimensions are configured, the first path segment will the identifiers of the dimension
     * values according to the current context.
     *
     * @param NodeInterface $node The node where the generated path should lead to
     * @return string The relative route path, possibly prefixed with a segment for identifying the current content dimension values
     */
    protected function resolveRoutePathForNode(NodeInterface $node)
    {
        $workspaceName = $node->getContext()->getWorkspaceName();

        $nodeContextPath = $node->getContextPath();
        $nodeContextPathSuffix = ($workspaceName !== 'live') ? substr($nodeContextPath, strpos($nodeContextPath, '@')) : '';

        $currentNodeIsSiteNode = ($node->getParentPath() === SiteService::SITES_ROOT_PATH);
        $dimensionsUriSegment = $this->getUriSegmentForDimensions($node->getContext()->getDimensions(), $currentNodeIsSiteNode);
        $requestPath = $this->getRequestPathByNode($node);

        return trim($dimensionsUriSegment . $requestPath, '/') . $nodeContextPathSuffix;
    }

    /**
     * Builds a node path which matches the given request path.
     *
     * This method traverses the segments of the given request path and tries to find nodes on the current level which
     * have a matching "uriPathSegment" property. If no node could be found which would match the given request path,
     * false is returned.
     *
     * @param NodeInterface $siteNode The site node, used as a starting point while traversing the tree
     * @param string $relativeRequestPath The request path, relative to the site's root path
     * @throws \Neos\Neos\Routing\Exception\NoSuchNodeException
     * @return string
     */
    protected function getRelativeNodePathByUriPathSegmentProperties(NodeInterface $siteNode, $relativeRequestPath)
    {
        $relativeNodePathSegments = [];
        $node = $siteNode;

        foreach (explode('/', $relativeRequestPath) as $pathSegment) {
            $foundNodeInThisSegment = false;
            foreach ($node->getChildNodes('Neos.Neos:Document') as $node) {
                /** @var NodeInterface $node */
                if ($node->getProperty('uriPathSegment') === $pathSegment) {
                    $relativeNodePathSegments[] = $node->getName();
                    $foundNodeInThisSegment = true;
                    break;
                }
            }
            if (!$foundNodeInThisSegment) {
                return false;
            }
        }

        return implode('/', $relativeNodePathSegments);
    }

    /**
     * Renders a request path based on the "uriPathSegment" properties of the nodes leading to the given node.
     *
     * @param NodeInterface $node The node where the generated path should lead to
     * @return string A relative request path
     * @throws Exception\MissingNodePropertyException if the given node doesn't have a "uriPathSegment" property set
     */
    protected function getRequestPathByNode(NodeInterface $node)
    {
        if ($node->getParentPath() === SiteService::SITES_ROOT_PATH) {
            return '';
        }

        // To allow building of paths to non-hidden nodes beneath hidden nodes, we assume
        // the input node is allowed to be seen and we must generate the full path here.
        // To disallow showing a node actually hidden itself has to be ensured in matching
        // a request path, not in building one.
        $contextProperties = $node->getContext()->getProperties();
        $contextAllowingHiddenNodes = $this->contextFactory->create(array_merge($contextProperties, ['invisibleContentShown' => true]));
        $currentNode = $contextAllowingHiddenNodes->getNodeByIdentifier($node->getIdentifier());

        $requestPathSegments = [];
        while ($currentNode instanceof NodeInterface && $currentNode->getParentPath() !== SiteService::SITES_ROOT_PATH) {
            if (!$currentNode->hasProperty('uriPathSegment')) {
                throw new Exception\MissingNodePropertyException(sprintf('Missing "uriPathSegment" property for node "%s". Nodes can be migrated with the "flow node:repair" command.', $node->getPath()), 1415020326);
            }

            $pathSegment = $currentNode->getProperty('uriPathSegment');
            $requestPathSegments[] = $pathSegment;
            $currentNode = $currentNode->getParent();
        }

        return implode('/', array_reverse($requestPathSegments));
    }

    /**
     * Sets context properties like "invisibleContentShown" according to the workspace (live or not) and returns a
     * ContentContext object.
     *
     * @param string $workspaceName Name of the workspace to use in the context
     * @param array $dimensionsAndDimensionValues An array of dimension names (index) and their values (array of strings). See also: ContextFactory
     * @return ContentContext
     */
    protected function buildContextFromWorkspaceNameAndDimensions($workspaceName, array $dimensionsAndDimensionValues)
    {
        $contextProperties = [
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => ($workspaceName !== 'live'),
            'inaccessibleContentShown' => ($workspaceName !== 'live'),
            'dimensions' => $dimensionsAndDimensionValues
        ];

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
    protected function getUriSegmentForDimensions(array $dimensionsValues, $currentNodeIsSiteNode)
    {
        $uriSegment = '';
        $allDimensionPresetsAreDefault = true;

        foreach ($this->contentDimensionPresetSource->getAllPresets() as $dimensionName => $dimensionPresets) {
            $detectionMode = $dimensionPresets['detectionMode'] ?? DetectContentSubgraphComponent::DETECTION_MODE_URIPATHSEGMENT;
            if ($detectionMode !== DetectContentSubgraphComponent::DETECTION_MODE_URIPATHSEGMENT) {
                continue;
            }
            $preset = null;
            if (isset($dimensionsValues[$dimensionName])) {
                $preset = $this->contentDimensionPresetSource->findPresetByDimensionValues($dimensionName, $dimensionsValues[$dimensionName]);
            }
            $defaultPreset = $this->contentDimensionPresetSource->getDefaultPreset($dimensionName);
            if ($preset === null) {
                $preset = $defaultPreset;
            }
            if ($preset !== $defaultPreset) {
                $allDimensionPresetsAreDefault = false;
            }
            if (!isset($preset['uriSegment'])) {
                throw new \Exception(sprintf('No "uriSegment" configured for content dimension preset "%s" for dimension "%s". Please check the content dimension configuration in Settings.yaml', $preset['identifier'], $dimensionName), 1395824520);
            }
            $uriSegment .= $preset['uriSegment'] . '_';
        }

        if ($this->supportEmptySegmentForDimensions && $allDimensionPresetsAreDefault && $currentNodeIsSiteNode) {
            return '/';
        } else {
            return ltrim(trim($uriSegment, '_') . '/', '/');
        }
    }
}
