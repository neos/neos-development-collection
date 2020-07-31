<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
class HomepageRoutePartHandler extends AbstractRoutePartHandler
{

    public function matchWithParameters(&$requestPath, RouteParameters $parameters)
    {
        $uriPathSegmentOffset = $parameters->getValue('uriPathSegmentOffset') ?? 0;
        $remainingRequestPath = $this->truncateRequestPathAndReturnRemainder($requestPath, $uriPathSegmentOffset);

        if ($requestPath !== '' || !$parameters->has('dimensionSpacePoint')) {
            return false;
        }
        // TODO verify parameters

        $site = $this->getCurrentSite($parameters);
        /** @var DimensionSpacePoint $dimensionSpacePoint */
        $dimensionSpacePoint = $parameters->getValue('dimensionSpacePoint');
        $nodeAddress = $this->documentUriPathFinder->findHomepageNodeAddressForSiteAndDimensionSpacePoint($site, $dimensionSpacePoint);
        if ($nodeAddress === null) {
            return false;
        }

        // todo populate $tagArray
        $tagArray = [];
        $requestPath = $remainingRequestPath;

        return new MatchResult($nodeAddress->serializeForUri(), RouteTags::createFromArray($tagArray));
    }

    public function resolve(array &$routeValues)
    {
        if ($this->name === null || $this->name === '' || !\array_key_exists($this->name, $routeValues)) {
            return false;
        }
        // TODO verify parameters

        $nodeAddress = $routeValues[$this->name];
        if (!$nodeAddress instanceof NodeAddress) {
            return false;
        }
        $uriPath = $this->documentUriPathFinder->findUriPathForNodeAddress($nodeAddress);
        if ($uriPath !== '') {
            return false;
        }

        unset($routeValues[$this->name]);
        $uriConstraints = $this->contentSubgraphUriProcessor->resolveDimensionUriConstraints($nodeAddress, true);
        return new ResolveResult('', $uriConstraints);
    }
}
