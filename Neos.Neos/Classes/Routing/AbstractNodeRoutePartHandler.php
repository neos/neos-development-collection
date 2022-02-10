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

use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\AbstractRoutePart;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\DynamicRoutePartInterface;
use Neos\Flow\Mvc\Routing\ParameterAwareRoutePartInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Neos\Domain\Service\NodeShortcutResolver;
use Neos\Neos\Exception as NeosException;
use Neos\Neos\Routing\Exception\InvalidDimensionPresetCombinationException;
use Neos\Neos\Routing\Exception\InvalidRequestPathException;
use Neos\Neos\Routing\Exception\NoSuchDimensionValueException;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 */
abstract class AbstractNodeRoutePartHandler extends AbstractRoutePart implements DynamicRoutePartInterface, ParameterAwareRoutePartInterface
{
    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

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
     * @var ContentContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * @Flow\Inject
     * @var NodeShortcutResolver
     */
    protected $nodeShortcutResolver;

    /**
     * @Flow\InjectConfiguration("routing.supportEmptySegmentForDimensions", package="Neos.Neos")
     * @var boolean
     */
    protected $supportEmptySegmentForDimensions;

    /**
     * @var Site[]
     */
    private array $cachedSites = [];

    /**
     * @var string
     */
    protected string $splitString = '';

    public function setSplitString($splitString): void
    {
        $this->splitString = $splitString;
    }

    public function match(&$routePath)
    {
        throw new \BadMethodCallException('match() is not supported by this Route Part Handler, use "matchWithParameters" instead', 1644495550);
    }

    public function resolve(array &$routeValues)
    {
        throw new \BadMethodCallException('resolve() is not supported by this Route Part Handler, use "resolveWithParameters" instead', 1644496338);
    }

    protected function contextPath(string $nodePath, array $dimensionValues): string
    {
        return NodePaths::generateContextPath($nodePath, 'live', $dimensionValues);
    }

    protected function getCurrentSiteNodePath(RouteParameters $parameters): string
    {
        return '/sites/' . $this->getCurrentSite($parameters)->getNodeName();
    }

    /**
     * @throws Exception\NoSiteException
     */
    private function getCurrentSite(RouteParameters $parameters): Site
    {
        $parametersId = $parameters->getCacheEntryIdentifier();
        if (!isset($this->cachedSites[$parametersId])) {
            if (!$parameters->has('requestUriHost')) {
                throw new Exception\NoSiteException('Failed to determine current site because the "requestUriHost" Routing parameter is not set', 1604860219);
            }
            $this->cachedSites[$parametersId] = $this->getSiteByHostName($parameters->getValue('requestUriHost'));
        }
        return $this->cachedSites[$parametersId];
    }

    /**
     * @throws Exception\NoSiteException
     */
    private function getSiteByHostName(string $hostName): Site
    {
        $domain = $this->domainRepository->findOneByHost($hostName, true);
        if ($domain !== null) {
            return $domain->getSite();
        }
        try {
            $defaultSite = $this->siteRepository->findDefault();
            if ($defaultSite === null) {
                throw new Exception\NoSiteException('Failed to determine current site because no default site is configured', 1604929674);
            }
        } catch (NeosException $exception) {
            throw new Exception\NoSiteException(sprintf('Failed to determine current site because no domain is specified matching host of "%s" and no default site could be found: %s', $hostName, $exception->getMessage()), 1604860219, $exception);
        }
        return $defaultSite;
    }

    /**
     * Choose between default method for parsing dimensions or the one which allows uriSegment to be empty for default preset.
     *
     * @param string &$requestPath The request path currently being processed by this route part handler, e.g. "de_global/startseite/ueber-uns"
     * @return array An array of dimension name => dimension values (array of string)
     * @throws InvalidDimensionPresetCombinationException
     * @throws InvalidRequestPathException
     * @throws NoSuchDimensionValueException
     */
    protected function parseDimensionsAndNodePathFromRequestPath(string &$requestPath): array
    {
        if ($this->supportEmptySegmentForDimensions) {
            $dimensionsAndDimensionValues = $this->parseDimensionsAndNodePathFromRequestPathAllowingEmptySegment($requestPath);
        } else {
            $dimensionsAndDimensionValues = $this->parseDimensionsAndNodePathFromRequestPathAllowingNonUniqueSegment($requestPath);
        }
        return $dimensionsAndDimensionValues;
    }

    /**
     * Parses the given request path and checks if the first path segment is one or a set of content dimension preset
     * identifiers. If that is the case, the return value is an array of dimension names and their preset URI segments.
     * Allows uriSegment to be empty for default dimension preset.
     *
     * If the first path segment contained content dimension information, it is removed from &$requestPath.
     *
     * @param string &$requestPath The request path currently being processed by this route part handler, e.g. "de_global/startseite/ueber-uns"
     * @return array An array of dimension name => dimension values (array of string)
     * @throws InvalidDimensionPresetCombinationException
     */
    private function parseDimensionsAndNodePathFromRequestPathAllowingEmptySegment(string &$requestPath): array
    {
        $dimensionPresets = $this->contentDimensionPresetSource->getAllPresets();
        if (count($dimensionPresets) === 0) {
            return [];
        }
        $dimensionsAndDimensionValues = [];
        $chosenDimensionPresets = [];
        $requestPathParts = explode('/', $requestPath, 2);
        $firstUriPartIsValidDimension = true;
        foreach ($dimensionPresets as $dimensionName => $dimensionPreset) {
            $dimensionsAndDimensionValues[$dimensionName] = $dimensionPreset['presets'][$dimensionPreset['defaultPreset']]['values'];
            $chosenDimensionPresets[$dimensionName] = $dimensionPreset['defaultPreset'];
        }
        if ($requestPathParts[0] !== '') {
            $firstUriPartExploded = explode('_', $requestPathParts[0]);
            foreach ($firstUriPartExploded as $uriSegment) {
                $uriSegmentIsValid = false;
                foreach ($dimensionPresets as $dimensionName => $dimensionPreset) {
                    $preset = $this->contentDimensionPresetSource->findPresetByUriSegment($dimensionName, $uriSegment);
                    if ($preset !== null) {
                        $uriSegmentIsValid = true;
                        $dimensionsAndDimensionValues[$dimensionName] = $preset['values'];
                        $chosenDimensionPresets[$dimensionName] = $preset['identifier'];
                        break;
                    }
                }
                if (!$uriSegmentIsValid) {
                    $firstUriPartIsValidDimension = false;
                    break;
                }
            }
            if ($firstUriPartIsValidDimension) {
                $requestPath = $requestPathParts[1] ?? '';
            }
        }
        if (!$this->contentDimensionPresetSource->isPresetCombinationAllowedByConstraints($chosenDimensionPresets)) {
            throw new InvalidDimensionPresetCombinationException(sprintf('The resolved content dimension preset combination (%s) is invalid or restricted by content dimension constraints. Check your content dimension settings if you think that this is an error.', implode(', ', array_keys($chosenDimensionPresets))), 1428657721);
        }
        return $dimensionsAndDimensionValues;
    }

    /**
     * Parses the given request path and checks if the first path segment is one or a set of content dimension preset
     * identifiers. If that is the case, the return value is an array of dimension names and their preset URI segments.
     * Doesn't allow empty uriSegment, but allows uriSegment to be not unique across presets.
     *
     * If the first path segment contained content dimension information, it is removed from &$requestPath.
     *
     * @param string &$requestPath The request path currently being processed by this route part handler, e.g. "de_global/startseite/ueber-uns"
     * @return array An array of dimension name => dimension values (array of string)
     * @throws InvalidDimensionPresetCombinationException
     * @throws InvalidRequestPathException
     * @throws NoSuchDimensionValueException
     */
    protected function parseDimensionsAndNodePathFromRequestPathAllowingNonUniqueSegment(string &$requestPath): array
    {
        $dimensionPresets = $this->contentDimensionPresetSource->getAllPresets();
        if (count($dimensionPresets) === 0) {
            return [];
        }

        $dimensionsAndDimensionValues = [];
        $chosenDimensionPresets = [];
        $requestPathParts = explode('/', $requestPath, 2);
        if ($requestPathParts[0] === '') {
            foreach ($dimensionPresets as $dimensionName => $dimensionPreset) {
                $dimensionsAndDimensionValues[$dimensionName] = $dimensionPreset['presets'][$dimensionPreset['defaultPreset']]['values'];
                $chosenDimensionPresets[$dimensionName] = $dimensionPreset['defaultPreset'];
            }
        } else {
            $firstUriPart = explode('_', $requestPathParts[0]);

            if (count($firstUriPart) !== count($dimensionPresets)) {
                throw new InvalidRequestPathException(sprintf('The first path segment of the request URI (%s) does not contain the necessary content dimension preset identifiers for all configured dimensions. This might be an old URI which doesn\'t match the current dimension configuration anymore.', $requestPath), 1413389121);
            }

            foreach ($dimensionPresets as $dimensionName => $dimensionPreset) {
                $uriSegment = array_shift($firstUriPart);
                $preset = $this->contentDimensionPresetSource->findPresetByUriSegment($dimensionName, $uriSegment);
                if ($preset === null) {
                    throw new NoSuchDimensionValueException(sprintf('Could not find a preset for content dimension "%s" through the given URI segment "%s".', $dimensionName, $uriSegment), 1413389321);
                }
                $dimensionsAndDimensionValues[$dimensionName] = $preset['values'];
                $chosenDimensionPresets[$dimensionName] = $preset['identifier'];
            }

            $requestPath = $requestPathParts[1] ?? '';
        }

        if (!$this->contentDimensionPresetSource->isPresetCombinationAllowedByConstraints($chosenDimensionPresets)) {
            throw new InvalidDimensionPresetCombinationException(sprintf('The resolved content dimension preset combination (%s) is invalid or restricted by content dimension constraints. Check your content dimension settings if you think that this is an error.', implode(', ', array_keys($chosenDimensionPresets))), 1462175794);
        }

        return $dimensionsAndDimensionValues;
    }

    protected function getContext(RouteParameters $parameters, array $dimensionsAndDimensionValues): Context
    {
        return $this->contextFactory->create([
            'dimensions' => $dimensionsAndDimensionValues,
            'currentSite' => $this->getCurrentSite($parameters)
        ]);
    }
}
