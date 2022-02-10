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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;

/**
 * Custom Route Part Handler that only supports homepage nodes in the live workspace
 */
final class HomepageRoutePartHandler extends AbstractNodeRoutePartHandler
{

    public function matchWithParameters(&$routePath, RouteParameters $parameters)
    {
        $dimensionValues = $this->parseDimensionsAndNodePathFromRequestPath($routePath);
        if ($routePath !== '') {
            return false;
        }
        $siteNodePath = $this->getCurrentSiteNodePath($parameters);
        return new MatchResult($this->contextPath($siteNodePath, $dimensionValues));
    }

    public function resolveWithParameters(array &$routeValues, RouteParameters $parameters)
    {
        return false;
        if ($this->name === null || $this->name === '' || !\array_key_exists($this->name, $routeValues)) {
            return false;
        }
        // Throw exception if dimensions are configured
        $this->dimensionValues();

        $node = $routeValues[$this->name];
        if (!$node instanceof NodeInterface || !$node->getContext()->isLive()) {
            return false;
        }
        if ($node->getParentPath() !== '/sites') {
            return false;
        }
        unset($routeValues[$this->name]);
        return new ResolveResult('', null, RouteTags::createFromTag($node->getIdentifier()));
    }
}
