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

use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentSubgraphUriProcessor;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection\DocumentUriPathFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\AbstractRoutePart;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\DynamicRoutePartInterface;
use Neos\Flow\Mvc\Routing\ParameterAwareRoutePartInterface;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * Common base class for the event sourced frontend node route part handlers
 */
abstract class AbstractRoutePartHandler extends AbstractRoutePart implements DynamicRoutePartInterface, ParameterAwareRoutePartInterface
{
    /**
     * @Flow\Inject
     * @var DocumentUriPathFinder
     */
    protected $documentUriPathFinder;

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
     * @var ContentSubgraphUriProcessor
     */
    protected $contentSubgraphUriProcessor;

    /**
     * @var Site[]
     */
    private $cachedSites = [];

    /**
     * @var string
     */
    protected $splitString = '';

    public function setSplitString($splitString): void
    {
        $this->splitString = $splitString;
    }

    final public function match(&$requestPath)
    {
        throw new \BadMethodCallException('match() is not supported by this Route Part Handler, use "matchWithParameters" instead', 1568287772);
    }

    protected function getCurrentSite(RouteParameters $parameters): Site
    {
        $parametersId = $parameters->getCacheEntryIdentifier();
        if (!isset($this->cachedSites[$parametersId])) {
            if (!$parameters->has('host')) {
                throw new \RuntimeException('Missing "host" Routing Parameter', 1568114996);
            }
            $domain = $this->domainRepository->findOneByHost($parameters->getValue('host'), true);
            if ($domain !== null) {
                $this->cachedSites[$parametersId] = $domain->getSite();
            } else {
                $this->cachedSites[$parametersId] = $this->siteRepository->findFirstOnline();
                if ($this->cachedSites[$parametersId] === null) {
                    throw new \RuntimeException(sprintf('Failed to find active site for "host" parameter "%s"', $parameters->getValue('host')), 1568115058);
                }
            }
        }
        return $this->cachedSites[$parametersId];
    }

    protected function truncateRequestPathAndReturnRemainder(string &$requestPath, int $uriPathSegmentOffset): string
    {
        $uriPathSegments = array_slice(explode('/', $requestPath), $uriPathSegmentOffset);
        $requestPath = implode('/', $uriPathSegments);
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
}
