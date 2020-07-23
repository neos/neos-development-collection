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
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection\DocumentUriPathFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\DynamicRoutePart;
use Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
class EventSourcedFrontendNodeRoutePartHandler extends DynamicRoutePart implements FrontendNodeRoutePartHandlerInterface
{
    /**
     * @Flow\Inject
     * @var DocumentUriPathFinder
     */
    protected $documentUriPathFinder;

    protected function findValueToMatch($requestPath): string
    {
        if ($this->splitString !== '') {
            $splitStringPosition = strpos($requestPath, $this->splitString);
            if ($splitStringPosition !== false) {
                return substr($requestPath, 0, $splitStringPosition);
            }
        }

        return $requestPath;
    }

    protected function matchValue($requestPath)
    {
        if (!$this->parameters->has('dimensionSpacePoint')) {
            return false;
        }

        $uriPathSegmentOffset = $this->parameters->getValue('uriPathSegmentOffset') ?? 0;
        $stripedRequestPath = implode('/', array_slice(explode('/', $requestPath), $uriPathSegmentOffset));
        /** @var DimensionSpacePoint $dimensionSpacePoint */
        $dimensionSpacePoint = $this->parameters->getValue('dimensionSpacePoint');
        $nodeAddress = $this->documentUriPathFinder->findNodeAddressForRequestPathAndDimensionSpacePoint($stripedRequestPath, $dimensionSpacePoint);
        if ($nodeAddress === null) {
            return false;
        }

        // todo populate $tagArray
        $tagArray = [];

        return new MatchResult($nodeAddress->serializeForUri(), RouteTags::createFromArray($tagArray));
    }

    protected function resolveValue($nodeAddress)
    {
        if (!$nodeAddress instanceof NodeAddress) {
            return false;
        }
        $uriPath = $this->documentUriPathFinder->findUriPathForNodeAddress($nodeAddress);
        if ($uriPath === null) {
            return false;
        }
        // TODO populate UriConstraints

        return new ResolveResult($uriPath, UriConstraints::create());
    }
}
