<?php
declare(strict_types=1);
namespace Neos\Neos\EventSourcedRouting\Http;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Neos\EventSourcedRouting\Routing\WorkspaceNameAndDimensionSpacePointForUriSerialization;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The HTTP component for detecting the requested dimension space point
 */
final class DetectContentSubgraphMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject
     * @var Dimension\ContentDimensionSourceInterface
     */
    protected $dimensionSource;

    /**
     * @Flow\Inject
     * @var ContentDimensionDetection\ContentDimensionValueDetectorResolver
     */
    protected $contentDimensionPresetDetectorResolver;

    /**
     * @Flow\InjectConfiguration(path="contentDimensions.resolution.uriPathSegmentDelimiter")
     * @var string
     */
    protected $uriPathSegmentDelimiter;

    /**
     * @Flow\InjectConfiguration("routing.supportEmptySegmentForDimensions", package="Neos.Neos")
     * @var boolean
     */
    protected $supportEmptySegmentForDimensions;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $uriPathSegmentUsed = false;

        $existingParameters = $request->getAttribute(Http\ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();
        $parameters = $existingParameters
            ->withParameter('dimensionSpacePoint', $this->detectDimensionSpacePoint($request, $uriPathSegmentUsed))
            ->withParameter('uriPathSegmentOffset', $uriPathSegmentUsed ? 1 : 0);
        return $next->handle($request->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, $parameters));
    }

    /**
     * @param ServerRequestInterface $request
     * @param bool $uriPathSegmentUsed
     * @return DimensionSpacePoint
     * @throws ContentDimensionDetection\Exception\InvalidContentDimensionValueDetectorException
     */
    protected function detectDimensionSpacePoint(
        ServerRequestInterface $request,
        bool &$uriPathSegmentUsed
    ): DimensionSpacePoint {
        $coordinates = [];
        $path = $request->getUri()->getPath();

        /** @todo no more paths! */
        $isParseableBackendUri = WorkspaceNameAndDimensionSpacePointForUriSerialization::isParseablebackendUri($path);
        $backendUriDimensionPresetDetector = new ContentDimensionDetection\BackendUriContentDimensionValueDetector();
        $dimensions = $this->dimensionSource->getContentDimensionsOrderedByPriority();
        $this->sortDimensionsByOffset($dimensions);
        $uriPathSegmentOffset = 0;
        foreach ($dimensions as $rawDimensionIdentifier => $contentDimension) {
            /** @var string $rawDimensionIdentifier should be clear, though... */
            $detector = $this->contentDimensionPresetDetectorResolver
                ->resolveContentDimensionValueDetector($contentDimension);

            $detectorOverrideOptions = $contentDimension->getConfigurationValue('resolution.options') ?? [];
            $resolutionMode = $contentDimension->getConfigurationValue('resolution.mode')
                ?? BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT;
            if ($resolutionMode === BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT) {
                $detectorOverrideOptions['delimiter'] = $this->uriPathSegmentDelimiter;
                if (!isset($detectorOverrideOptions['offset'])) {
                    $detectorOverrideOptions['offset'] = $uriPathSegmentOffset;
                }
            }

            if ($isParseableBackendUri) {
                $dimensionValue = $backendUriDimensionPresetDetector->detectValue($contentDimension, $request);
                if ($dimensionValue) {
                    $coordinates[$rawDimensionIdentifier] = (string)$dimensionValue;
                    if ($detector instanceof ContentDimensionDetection\UriPathSegmentContentDimensionValueDetector) {
                        // we might have to remove the uri path segment anyway
                        $dimensionValueByUriPathSegment = $detector->detectValue(
                            $contentDimension,
                            $request,
                            $detectorOverrideOptions
                        );
                        if ($dimensionValueByUriPathSegment) {
                            $uriPathSegmentUsed = true;
                        }
                    }
                    continue;
                }
            }

            $dimensionValue = $detector->detectValue($contentDimension, $request, $detectorOverrideOptions);
            if ($dimensionValue) {
                $coordinates[$rawDimensionIdentifier] = (string)$dimensionValue;
                if ($resolutionMode === BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT) {
                    $uriPathSegmentUsed = true;
                    $uriPathSegmentOffset++;
                }
            } else {
                $allowEmptyValue = ($detectorOverrideOptions['allowEmptyValue'] ?? false)
                    || $resolutionMode === BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT
                        && $this->supportEmptySegmentForDimensions;
                if ($allowEmptyValue
                    || $resolutionMode === BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT
                        && $path === '/'
                ) {
                    $coordinates[$rawDimensionIdentifier] = (string)$contentDimension->defaultValue;
                }
            }
        }

        return DimensionSpacePoint::fromArray($coordinates);
    }

    /**
     * @param array|Dimension\ContentDimension[] $dimensions
     * @return void
     */
    protected function sortDimensionsByOffset(array &$dimensions)
    {
        uasort($dimensions, function (Dimension\ContentDimension $dimensionA, Dimension\ContentDimension $dimensionB) {
            return ($dimensionA->getConfigurationValue('resolution.options.offset') ?: 0)
                <=> ($dimensionB->getConfigurationValue('resolution.options.offset') ?: 0);
        });
    }
}
