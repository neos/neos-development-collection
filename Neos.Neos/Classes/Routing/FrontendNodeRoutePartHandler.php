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

use GuzzleHttp\Psr7\Uri;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Service\SiteService;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Custom Route Part Handler that only supports nodes that are in live workspace and not
 * the homepage node (i.e. direct descendant of /sites)
 */
final class FrontendNodeRoutePartHandler extends AbstractNodeRoutePartHandler implements FrontendNodeRoutePartHandlerInterface
{
    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    public function matchWithParameters(&$routePath, RouteParameters $parameters)
    {
        if ($routePath === '' || !is_string($routePath)) {
            return false;
        }
        $dimensionValues = $this->parseDimensionsAndNodePathFromRequestPath($routePath);
        $this->truncateUriPathSuffix($routePath);
        $siteNodePath = $this->getCurrentSiteNodePath($parameters);

        $siteNode = null;
        $this->securityContext->withoutAuthorizationChecks(function () use (&$siteNode, $parameters, $dimensionValues, $siteNodePath) {
            /** @var TraversableNodeInterface $siteNode */
            $siteNode = $this->getContext($parameters, $dimensionValues)->getNode($siteNodePath);
        });

        $affectedNodeIdentifiers = [];
        $node = $this->getNodeByRequestUriPath($siteNode, $routePath, $affectedNodeIdentifiers);
        if ($node === $siteNode || !$this->nodeTypeIsAllowed($node)) {
            return false;
        }
        return new MatchResult($node->getContextPath(), $this->routeTagsFromIdentifiers($affectedNodeIdentifiers));
    }

    public function resolveWithParameters(array &$routeValues, RouteParameters $parameters)
    {
        if ($this->name === null || $this->name === '' || !\array_key_exists($this->name, $routeValues)) {
            return false;
        }

        $node = $routeValues[$this->name];
        if (!$node instanceof TraversableNodeInterface || !$node->getContext()->isLive()) {
            return false;
        }
        unset($routeValues[$this->name]);
        try {
            $nodeOrUri = $this->resolveShortcutNode($node);
        } catch (Exception\InvalidShortcutException $exception) {
            $this->systemLogger->debug('FrontendNodeRoutePartHandler resolveValue(): ' . $exception->getMessage());
            return false;
        }
        if ($nodeOrUri instanceof UriInterface) {
            return new ResolveResult('', UriConstraints::fromUri($nodeOrUri), null);
        }

        try {
            $uriConstraints = $this->buildUriConstraintsForResolvedNode($nodeOrUri, $parameters);
        } catch (Exception\NoSiteException $exception) {
            $this->systemLogger->debug('FrontendNodeRoutePartHandler resolveValue(): ' . $exception->getMessage());
            return false;
        }
        $affectedNodeIdentifiers = [];
        try {
            $requestPath = $this->getRequestPathByNode($nodeOrUri, $affectedNodeIdentifiers);
        } catch (Exception\MissingNodePropertyException $exception) {
            $this->systemLogger->debug('FrontendNodeRoutePartHandler resolveValue(): ' . $exception->getMessage());
            return false;
        }
        return new ResolveResult($requestPath, $uriConstraints, $this->routeTagsFromIdentifiers($affectedNodeIdentifiers));
    }

    // ---------------------

    /**
     * @throws Exception\InvalidRequestPathException
     */
    protected function truncateUriPathSuffix(string &$requestPath): void
    {
        if (empty($this->options['uriPathSuffix'])) {
            return;
        }
        $suffixLength = strlen($this->options['uriPathSuffix']);
        if (substr($requestPath, -$suffixLength) !== $this->options['uriPathSuffix']) {
            throw new Exception\InvalidRequestPathException(sprintf('The request path "%s" doesn\'t contain the configured uriPathSuffix "%s"', $requestPath, $this->options['uriPathSuffix']), 1604912439);
        }
        $requestPath = substr($requestPath, 0, -$suffixLength);
    }

    /**
     * Whether the given $node is allowed according to the "nodeType" option
     *
     * @param TraversableNodeInterface $node
     * @return bool
     */
    private function nodeTypeIsAllowed(TraversableNodeInterface $node): bool
    {
        $allowedNodeType = !empty($this->options['nodeType']) ? $this->options['nodeType'] : 'Neos.Neos:Document';
        return $node->getNodeType()->isOfType($allowedNodeType);
    }

    /**
     * Builds a node path which matches the given request path.
     *
     * This method traverses the segments of the given request path and tries to find nodes on the current level which
     * have a matching "uriPathSegment" property. If no node could be found which would match the given request path,
     * false is returned.
     *
     * @param TraversableNodeInterface $siteNode The site node, used as a starting point while traversing the tree
     * @param string $requestPath The request path, relative to the site's root path
     * @param array $affectedNodeIdentifiers
     * @return TraversableNodeInterface
     */
    private function getNodeByRequestUriPath(TraversableNodeInterface $siteNode, string &$requestPath, array &$affectedNodeIdentifiers): TraversableNodeInterface
    {
        $affectedNodeIdentifiers = [(string)$siteNode->getNodeAggregateIdentifier()];
        $node = $siteNode;
        $nodeTypeConstraints = $this->nodeTypeConstraintFactory->parseFilterString('Neos.Neos:Document');

        $requestPathSegments = explode('/', $requestPath);
        do {
            $pathSegment = array_shift($requestPathSegments);
            foreach ($node->findChildNodes($nodeTypeConstraints) as $childNode) {
                if ($childNode->getProperty('uriPathSegment') === $pathSegment) {
                    $node = $childNode;
                    $affectedNodeIdentifiers[] = (string)$node->getNodeAggregateIdentifier();
                    continue 2;
                }
            }
            array_unshift($requestPathSegments, $pathSegment);
            break;
        } while ($requestPathSegments !== []);
        $requestPath = $requestPathSegments === [] ? '' : '/' . implode('/', $requestPathSegments);
        return $node;
    }

    private function getRequestPathByNode(TraversableNodeInterface $node, array &$affectedNodeIdentifiers): string
    {
        $affectedNodeIdentifiers = [(string)$node->getNodeAggregateIdentifier()];
        $requestPathSegments = [];
        while ($node->getParentPath() !== SiteService::SITES_ROOT_PATH) {
            if (!$node->hasProperty('uriPathSegment')) {
                throw new Exception\MissingNodePropertyException(sprintf('Missing "uriPathSegment" property for node "%s". Nodes can be migrated with the "flow node:repair" command.', $node->getPath()), 1415020326);
            }
            $requestPathSegments[] = $node->getProperty('uriPathSegment');
            $affectedNodeIdentifiers[] = (string)$node->getNodeAggregateIdentifier();
            $node = $node->findParentNode();
        }
        return implode('/', array_reverse($requestPathSegments));
    }

    private function routeTagsFromIdentifiers(array $identifiers): RouteTags
    {
        return RouteTags::createFromArray(array_filter($identifiers, static fn ($tag) => preg_match(FrontendInterface::PATTERN_TAG, $tag) === 1));
    }

    protected function resolveShortcutNode(TraversableNodeInterface $node)
    {
        if (!$node instanceof NodeInterface) {
            throw new Exception\InvalidShortcutException(sprintf('Could not resolve shortcut target for node "%s" that is not of type %s', $node->getPath(), NodeInterface::class), 1644521839);
        }
        $resolvedNode = $this->nodeShortcutResolver->resolveShortcutTarget($node);
        if (is_string($resolvedNode)) {
            return new Uri($resolvedNode);
        }
        if (!$resolvedNode instanceof TraversableNodeInterface) {
            throw new Exception\InvalidShortcutException(sprintf('Could not resolve shortcut target for node "%s"', $node->getPath()), 1414771137);
        }
        return $resolvedNode;
    }

    /**
     * Builds UriConstraints for the given $node with:
     * * domain specific constraints for nodes in a different Neos site
     * * a path suffix corresponding to the configured "uriPathSuffix"
     *
     * @param NodeInterface $node
     * @return UriConstraints
     * @throws Exception\NoSiteException This exception will be caught in resolveValue()
     */
    protected function buildUriConstraintsForResolvedNode(NodeInterface $node, RouteParameters $parameters): UriConstraints
    {
        $uriConstraints = UriConstraints::create();
        if (!NodePaths::isSubPathOf(SiteService::SITES_ROOT_PATH, $node->getPath())) {
            throw new Exception\NoSiteException(sprintf('The node at path "%s" is not located underneath the sites root path "%s"', $node->getPath(), SiteService::SITES_ROOT_PATH), 1604922914);
        }
        $requestSiteNodePath = $this->getCurrentSiteNodePath($parameters);
        $resolvedSiteNodeName = strtok(NodePaths::getRelativePathBetween(SiteService::SITES_ROOT_PATH, $node->getPath()), '/');
        if (!NodePaths::isSubPathOf($requestSiteNodePath, $node->getPath())) {
            $resolvedSite = $this->siteRepository->findOneByNodeName($resolvedSiteNodeName);
            if ($resolvedSite === null || $resolvedSite->isOffline()) {
                throw new Exception\NoSiteException(sprintf('No online site found for node "%s" and resolved site node name of "%s"', $node->getIdentifier(), $resolvedSiteNodeName), 1604505599);
            }
            $uriConstraints = $this->applyDomainToUriConstraints($uriConstraints, $resolvedSite->getPrimaryDomain());
        }
        if (!empty($this->options['uriPathSuffix'])) {
            $uriConstraints = $uriConstraints->withPathSuffix($this->options['uriPathSuffix']);
        }
        return $uriConstraints;
    }

    /**
     * @param UriConstraints $uriConstraints
     * @param Domain|null $domain
     * @return UriConstraints
     */
    protected function applyDomainToUriConstraints(UriConstraints $uriConstraints, ?Domain $domain): UriConstraints
    {
        if ($domain === null) {
            return $uriConstraints;
        }
        $uriConstraints = $uriConstraints->withHost($domain->getHostname());
        if (!empty($domain->getScheme())) {
            $uriConstraints = $uriConstraints->withScheme($domain->getScheme());
        }
        if (!empty($domain->getPort())) {
            $uriConstraints = $uriConstraints->withPort($domain->getPort());
        }
        return $uriConstraints;
    }
}
