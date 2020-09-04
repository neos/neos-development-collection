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
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Exception\InvalidShortcutException;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection\DocumentUriPathFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\AbstractRoutePart;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\DynamicRoutePartInterface;
use Neos\Flow\Mvc\Routing\ParameterAwareRoutePartInterface;
use Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
final class EventSourcedFrontendNodeRoutePartHandler extends AbstractRoutePart implements DynamicRoutePartInterface, ParameterAwareRoutePartInterface, FrontendNodeRoutePartHandlerInterface
{
    /**
     * @Flow\Inject
     * @var DocumentUriPathFinder
     */
    protected $documentUriPathFinder;

    /**
     * @var string
     */
    private $splitString = '';

    /**
     * @param string $requestPath
     * @param RouteParameters $parameters
     * @return bool|MatchResult
     */
    public function matchWithParameters(&$requestPath, RouteParameters $parameters)
    {
        if (!is_string($requestPath)) {
            return false;
        }
        // TODO verify parameters / use "host" parameter
        if (!$parameters->has('dimensionSpacePoint')) {
            return false;
        }

        $uriPathSegmentOffset = $parameters->getValue('uriPathSegmentOffset') ?? 0;
        $remainingRequestPath = $this->truncateRequestPathAndReturnRemainder($requestPath, $uriPathSegmentOffset);
        /** @var DimensionSpacePoint $dimensionSpacePoint */
        $dimensionSpacePoint = $parameters->getValue('dimensionSpacePoint');

        try {
            $matchResult = $this->documentUriPathFinder->matchUriPath($requestPath, $dimensionSpacePoint);
        } catch (InvalidShortcutException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            // TODO log exception
            return false;
        }
        $requestPath = $remainingRequestPath;
        return $matchResult;
    }

    /**
     * @param array $routeValues
     * @return ResolveResult|bool
     */
    public function resolve(array &$routeValues)
    {
        if ($this->name === null || $this->name === '' || !\array_key_exists($this->name, $routeValues)) {
            return false;
        }

        $nodeAddress = $routeValues[$this->name];
        if (!$nodeAddress instanceof NodeAddress) {
            return false;
        }

        try {
            $resolveResult = $this->documentUriPathFinder->resolveNodeAddress($nodeAddress, $this->options['uriSuffix'] ?? '');
        } catch (InvalidShortcutException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            // TODO log exception
            return false;
        }

        unset($routeValues[$this->name]);
        return $resolveResult;
    }

    private function truncateRequestPathAndReturnRemainder(string &$requestPath, int $uriPathSegmentOffset): string
    {
        $uriPathSegments = array_slice(explode('/', $requestPath), $uriPathSegmentOffset);
        $requestPath = implode('/', $uriPathSegments);
        if (!empty($this->options['uriSuffix'])) {
            $suffixPosition = strpos($requestPath, $this->options['uriSuffix']);
            if ($suffixPosition === false) {
                return '';
            }
            $requestPath = substr($requestPath, 0, $suffixPosition);
        }
        if ($this->splitString === '' || $this->splitString === '/') {
            return '';
        }
        $splitStringPosition = strpos($requestPath, $this->splitString);
        if ($splitStringPosition === false) {
            return '';
        }
        $fullRequestPath = $requestPath;
        $requestPath = substr($requestPath, 0, $splitStringPosition);

        return substr($fullRequestPath, $splitStringPosition);
    }

    public function setSplitString($splitString): void
    {
        $this->splitString = $splitString;
    }

    public function match(&$routePath)
    {
        throw new \BadMethodCallException('match() is not supported by this Route Part Handler, use "matchWithParameters" instead', 1568287772);
    }
}
