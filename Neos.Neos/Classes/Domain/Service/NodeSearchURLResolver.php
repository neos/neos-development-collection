<?php

namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Mvc\Routing\RouterInterface;

class NodeSearchURLResolver implements NodeSearchResolverInterface
{
    /**
     * @var RouterInterface
     */
    #[Flow\Inject]
    protected $router;

    /**
     * @param string[] $searchNodeTypes
     * @return NodeInterface[]
     */
    public function resolve(
        string $term,
        array $searchNodeTypes,
        Context $context,
        NodeInterface $startingPoint = null
    ): array {
        $uri = new Uri($term);

        $routeParameters = RouteParameters::createEmpty();
        $routeParameters = $routeParameters->withParameter('requestUriHost', $uri->getHost());

        $routeContext = new RouteContext(
            new ServerRequest('GET', $uri),
            $routeParameters
        );

        $router = new Router();

        try {
            $matches = $router->route($routeContext);
        } catch (\Exception) {
            return [];
        }

        if (!$matches) {
            return [];
        }

        $nodeContextPath = $matches['node'] ?? null;

        if (!$nodeContextPath) {
            return [];
        }

        $nodePath = NodePaths::explodeContextPath($nodeContextPath)['nodePath'];
        $matchingNode = $context->getNode($nodePath);
        if ($matchingNode && $this->nodeSatisfiesSearchNodeTypes($matchingNode, $searchNodeTypes)) {
            return [$matchingNode->getPath() => $matchingNode];
        }
        return [];
    }

    /**
     * This resolver accepts node paths only
     *
     * @param string[] $searchNodeTypes
     */
    public function matches(
        string $term,
        array $searchNodeTypes,
        Context $context,
        NodeInterface $startingPoint = null
    ): bool {
        return preg_match('/^https?:\/\/.*$/', $term) === 1;
    }

    /**
     * Whether the given $node satisfies the specified types
     *
     * @param string[] $searchNodeTypes
     */
    protected function nodeSatisfiesSearchNodeTypes(NodeInterface $node, array $searchNodeTypes): bool
    {
        foreach ($searchNodeTypes as $nodeTypeName) {
            if ($node->getNodeType()->isOfType($nodeTypeName)) {
                return true;
            }
        }
        return false;
    }
}
