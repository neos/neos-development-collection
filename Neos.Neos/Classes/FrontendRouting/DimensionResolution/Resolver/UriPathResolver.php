<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\DimensionResolution\RequestToDimensionSpacePointContext;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Segment;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\SegmentMappingElement;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Segments;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Separator;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\UriPathResolverConfigurationException;
use Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo;

/**
 * URI path segment based dimension value resolver
 *
 * Reads the first part of the URL segment (`/SOMETHING/`) and extracts the correct dimension
 * values from it. Supports multiple dimensions, such as "language" and "country".
 *
 * Each part of the first uri path is called a "segment" (once for each dimension).
 *
 * See {@see DimensionResolverInterface} for detailed documentation.
 */
final class UriPathResolver implements DimensionResolverInterface
{
    /**
     * @var DimensionSpacePoint default dimension space point reduced to the dimensions which this resolver is responsible for
     */
    private readonly DimensionSpacePoint $filteredDefaultDimensionSpacePoint;
    /**
     * @param array<string,DimensionSpacePoint> $uriPathToDimensionSpacePoint
     * @param array<string,string> $dimensionSpacePointHashToUriPath
     * @param Segments $segments
     */
    private function __construct(
        private readonly array $uriPathToDimensionSpacePoint,
        private readonly array $dimensionSpacePointHashToUriPath,
        private readonly Segments $segments,
        DimensionSpacePoint $defaultDimensionSpacePoint,
    ) {
        $this->filteredDefaultDimensionSpacePoint = $this->reduceDimensionSpacePointToConfiguredDimensions($defaultDimensionSpacePoint);
    }

    public static function create(
        Segments $segments,
        Separator $separator,
        ContentDimensionSourceInterface $contentDimensionSource,
        DimensionSpacePoint $defaultDimensionSpacePoint,
    ): self {
        self::validate($segments, $separator, $contentDimensionSource);
        list($uriPathToDimensionSpacePoint, $dimensionSpacePointHashToUriPath) = self::calculateUriPaths(
            $segments,
            $separator
        );
        return new self(
            $uriPathToDimensionSpacePoint,
            $dimensionSpacePointHashToUriPath,
            $segments,
            $defaultDimensionSpacePoint
        );
    }

    private static function validate(
        Segments $segments,
        Separator $separator,
        ContentDimensionSourceInterface $contentDimensionSource
    ): void {
        foreach ($segments->segments as $segment) {
            $contentDimension = $contentDimensionSource->getDimension($segment->dimensionIdentifier);
            if ($contentDimension === null) {
                throw new UriPathResolverConfigurationException(
                    'Content Dimension "' . $segment->dimensionIdentifier . '" does not exist.'
                );
            }

            foreach ($segment->uriPathSegmentMapping as $mappingElement) {
                if ($contentDimension->getValue($mappingElement->dimensionValue->value) === null) {
                    throw new UriPathResolverConfigurationException(
                        'Content Dimension Value "' . $mappingElement->dimensionValue->value
                            . '" in dimension "' . $segment->dimensionIdentifier->id . '" does not exist.'
                    );
                }

                if (str_contains($mappingElement->uriPathSegmentValue, $separator->value)) {
                    throw new UriPathResolverConfigurationException(
                        'The URI Path segment for dimension value "' . $mappingElement->dimensionValue->value
                            . '" contains the separator "' . $separator->value . '".'
                    );
                }
            }
        }
    }


    /**
     * @param Segments $segments
     * @param Separator $separator
     * @return array<mixed>
     */
    private static function calculateUriPaths(Segments $segments, Separator $separator): array
    {
        $uriPathToDimensionSpacePoint = [];
        $dimensionSpacePointHashToUriPath = [];
        foreach (self::cartesian($segments) as $validCombination) {
            $segmentParts = [];
            $dimensionSpacePointCoordinates = [];
            foreach ($validCombination as $dimensionName => $value) {
                assert($value instanceof SegmentMappingElement);
                if (!empty($value->uriPathSegmentValue)) {
                    $segmentParts[] = $value->uriPathSegmentValue;
                }

                $dimensionSpacePointCoordinates[$dimensionName] = $value->dimensionValue->value;
            }

            $uriPathSegment = implode($separator->value, $segmentParts);
            if (isset($uriPathToDimensionSpacePoint[$uriPathSegment])) {
                throw new UriPathResolverConfigurationException(
                    'Uri path segment "' . $uriPathSegment
                        . '" already configured by dimension ' . $uriPathToDimensionSpacePoint[$uriPathSegment]
                        . '. Thus, we cannot use it for dimension ' . json_encode($dimensionSpacePointCoordinates)
                );
            }
            $dimensionSpacePoint = DimensionSpacePoint::fromArray($dimensionSpacePointCoordinates);
            $uriPathToDimensionSpacePoint[$uriPathSegment] = $dimensionSpacePoint;
            $dimensionSpacePointHashToUriPath[$dimensionSpacePoint->hash] = $uriPathSegment;
        }

        return [$uriPathToDimensionSpacePoint, $dimensionSpacePointHashToUriPath];
    }

    /**
     * @param Segments $segments
     * @return array<int,array<string,SegmentMappingElement>>
     */
    private static function cartesian(Segments $segments): array
    {
        // taken and adapted from https://stackoverflow.com/a/15973172/4921449
        $result = [[]];

        foreach ($segments->segments as $segment) {
            $append = [];

            foreach ($result as $product) {
                foreach ($segment->uriPathSegmentMapping as $item) {
                    $product[$segment->dimensionIdentifier->id] = $item;
                    $append[] = $product;
                }
            }

            $result = $append;
        }

        return $result;
    }

    public function fromRequestToDimensionSpacePoint(
        RequestToDimensionSpacePointContext $context
    ): RequestToDimensionSpacePointContext {
        $normalizedUriPath = trim($context->initialUriPath, '/');
        $remainingUriPathSegments = explode('/', $normalizedUriPath);
        $firstUriPathSegment = array_shift($remainingUriPathSegments);

        if (!empty($firstUriPathSegment) && isset($this->uriPathToDimensionSpacePoint[$firstUriPathSegment])) {
            // URL starts with /en_US (f.e.)
            if (count($remainingUriPathSegments) === 0 && $this->uriPathToDimensionSpacePoint[$firstUriPathSegment]->hash === $this->filteredDefaultDimensionSpacePoint->hash) {
                // user calls /en_US, but has this configured as default dimension space point.
                // / should resolve to the language:en_US
                // /en_US should NOT resolve. <----- WE ARE HERE
                //
                // this code is responsible for the last case ("/en_US should NOT resolve.").
                // ("I don't know how to handle /en_US -> RoutePartHandler tries to resolve a node at /en_US/....
                //  and (hopefully) won't find this node")
                return $context;
            }

            // URL starts with /en_US (and we are NOT in the homepage-special-case above)
            // -> we strip the /en_US segment from the URI path (for further processing)
            // -> we store the resolved dimension space point in context.
            $context = $context->withRemainingUriPath('/' . implode('/', $remainingUriPathSegments));
            return $context->withAddedDimensionSpacePoint(
                $this->uriPathToDimensionSpacePoint[$firstUriPathSegment]
            );
        } elseif (isset($this->uriPathToDimensionSpacePoint[''])) {
            // Fall-through empty match (if existing)
            // (empty URL prefix should map to f.e. en_US -> so all URLs without a prefix end up here.)
            return $context->withAddedDimensionSpacePoint($this->uriPathToDimensionSpacePoint['']);
        } elseif ($firstUriPathSegment === "") {
            // user calls /, and has configured a default DimensionSpacePoint without empty segments.
            // / should resolve to the language:en_US <----- WE ARE HERE
            // /en_US should NOT resolve.

            // I am on the homepage
            // -> fill with (filtered) default DSP
            return $context->withAddedDimensionSpacePoint($this->filteredDefaultDimensionSpacePoint);
        }

        return $context;
    }

    public function fromDimensionSpacePointToUriConstraints(
        DimensionSpacePoint $dimensionSpacePoint,
        DocumentNodeInfo $targetNodeInfo,
        UriConstraints $uriConstraints
    ): UriConstraints {
        $filteredDimensionSpacePoint = $this->reduceDimensionSpacePointToConfiguredDimensions($dimensionSpacePoint);
        unset($dimensionSpacePoint);

        if ($targetNodeInfo->getUriPath() === '' && $filteredDimensionSpacePoint->hash === $this->filteredDefaultDimensionSpacePoint->hash) {
            // link to homepage; AND default dimensions match:
            // generate a link to "/" (by not modifying the UriConstraints)
            return $uriConstraints;
        }

        if (isset($this->dimensionSpacePointHashToUriPath[$filteredDimensionSpacePoint->hash])) {
            $uriPath = $this->dimensionSpacePointHashToUriPath[$filteredDimensionSpacePoint->hash];
            if (strlen($uriPath) > 0) {
                return $uriConstraints->withPathPrefix($uriPath . '/', true);
            }
        }

        return $uriConstraints;
    }

    private function reduceDimensionSpacePointToConfiguredDimensions(DimensionSpacePoint $incoming): DimensionSpacePoint
    {
        $newCoordinates = [];
        foreach ($this->segments->segments as $segment) {
            $coordinateValue = $incoming->getCoordinate($segment->dimensionIdentifier);
            if ($coordinateValue !== null) {
                $newCoordinates[$segment->dimensionIdentifier->id] = $coordinateValue;
            }
        }
        return DimensionSpacePoint::fromArray($newCoordinates);
    }
}
