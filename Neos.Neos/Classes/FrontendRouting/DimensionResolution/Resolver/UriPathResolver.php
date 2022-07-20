<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\DimensionResolution\RequestToDimensionSpacePointContext;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\SegmentMappingElement;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Segments;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Separator;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\UriPathResolverConfigurationException;

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
    private function __construct(
        private readonly array $uriPathToDimensionSpacePoint,
        private readonly array $dimensionSpacePointHashToUriPath,
        private readonly Segments $segments,
    ) {
    }

    public static function create(
        Segments $segments,
        Separator $separator,
        ContentDimensionSourceInterface $contentDimensionSource
    ): self {
        self::validate($segments, $separator, $contentDimensionSource);
        list($uriPathToDimensionSpacePoint, $dimensionSpacePointHashToUriPath) = self::calculateUriPaths($segments, $separator);
        return new self(
            $uriPathToDimensionSpacePoint,
            $dimensionSpacePointHashToUriPath,
            $segments
        );
    }

    private static function validate(Segments $segments, Separator $separator, ContentDimensionSourceInterface $contentDimensionSource)
    {
        foreach ($segments->segments as $segment) {
            $contentDimension = $contentDimensionSource->getDimension($segment->dimensionIdentifier);
            if ($contentDimension === null) {
                throw new UriPathResolverConfigurationException('Content Dimension "' . $segment->dimensionIdentifier . '" does not exist.');
            }

            foreach ($segment->uriPathSegmentMapping as $mappingElement) {
                if ($contentDimension->getValue($mappingElement->dimensionValue->value) === null) {
                    throw new UriPathResolverConfigurationException('Content Dimension Value "' . $mappingElement->dimensionValue->value . '" in dimension "' . $segment->dimensionIdentifier->identifier . '" does not exist.');
                }

                if (str_contains($mappingElement->uriPathSegmentValue, $separator->value)) {
                    throw new UriPathResolverConfigurationException('The URI Path segment for dimension value "' . $mappingElement->dimensionValue->value . '" contains the separator "' . $separator->value . '".');
                }
            }
        }
    }


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
                throw new UriPathResolverConfigurationException('Uri path segment "' . $uriPathSegment . '" already configured by dimension ' . $uriPathToDimensionSpacePoint[$uriPathSegment] . '. Thus, we cannot use it for dimension ' . json_encode($dimensionSpacePointCoordinates));
            }
            $dimensionSpacePoint = DimensionSpacePoint::fromArray($dimensionSpacePointCoordinates);
            $uriPathToDimensionSpacePoint[$uriPathSegment] = $dimensionSpacePoint;
            $dimensionSpacePointHashToUriPath[$dimensionSpacePoint->hash] = $uriPathSegment;
        }

        return [$uriPathToDimensionSpacePoint, $dimensionSpacePointHashToUriPath];
    }

    private static function cartesian(Segments $segments): array
    {
        // taken and adapted from https://stackoverflow.com/a/15973172/4921449
        $result = [[]];

        foreach ($segments->segments as $segment) {
            $append = [];

            foreach ($result as $product) {
                foreach ($segment->uriPathSegmentMapping as $item) {
                    $product[$segment->dimensionIdentifier->identifier] = $item;
                    $append[] = $product;
                }
            }

            $result = $append;
        }

        return $result;
    }


    public function fromRequestToDimensionSpacePoint(RequestToDimensionSpacePointContext $context): RequestToDimensionSpacePointContext
    {
        $normalizedUriPath = trim($context->initialUriPath, '/');
        $uriPathSegments = explode('/', $normalizedUriPath);
        $firstUriPathSegment = array_shift($uriPathSegments);

        if (isset($this->uriPathToDimensionSpacePoint[$firstUriPathSegment])) {
            // match
            $context = $context->withRemainingUriPath('/' . implode('/', $uriPathSegments));
            $context = $context->withAddedDimensionSpacePoint($this->uriPathToDimensionSpacePoint[$firstUriPathSegment]);
        } elseif (isset($this->uriPathToDimensionSpacePoint[''])) {
            // Fall-through empty match (if configured)
            $context = $context->withAddedDimensionSpacePoint($this->uriPathToDimensionSpacePoint['']);
        }

        return $context;
    }

    public function fromDimensionSpacePointToUriConstraints(DimensionSpacePoint $dimensionSpacePoint, SiteNodeName $targetSiteIdentifier, UriConstraints $uriConstraints): UriConstraints
    {
        $dimensionSpacePoint = $this->reduceDimensionSpacePointToConfiguredDimensions($dimensionSpacePoint);

        if (isset($this->dimensionSpacePointHashToUriPath[$dimensionSpacePoint->hash])) {
            $uriPath = $this->dimensionSpacePointHashToUriPath[$dimensionSpacePoint->hash];
            if (strlen($uriPath) > 0) {
                return $uriConstraints->withPathPrefix($uriPath . '/', true);
            }
        }

        return $uriConstraints;
    }

    private function reduceDimensionSpacePointToConfiguredDimensions(DimensionSpacePoint $incoming)
    {
        $newCoordinates = [];
        foreach ($this->segments->segments as $segment) {
            $newCoordinates[$segment->dimensionIdentifier->identifier] = $incoming->getCoordinate($segment->dimensionIdentifier);
        }
        return DimensionSpacePoint::fromArray($newCoordinates);
    }
}
