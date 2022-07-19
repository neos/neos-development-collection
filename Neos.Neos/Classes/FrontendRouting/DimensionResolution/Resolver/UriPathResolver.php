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
use Neos\Neos\Domain\Model\SiteIdentifier;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverContext;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\SegmentMappingElement;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Segments;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\Separator;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver\UriPathResolverConfigurationException;

/**
 *
 * de/b2b
 * de_b2b
 * de___b2b
 *
 * b2b
 *
 * _b2b
 *
 * /de_b2b/foo
 *
 * URI path segment based dimension value resolver
 *
 * See {@see DimensionResolverInterface} for detailed documentation.
 */
final class UriPathResolver implements DimensionResolverInterface
{
    private function __construct(
        private readonly array $uriPathToDimensionSpacePoint,
        private readonly array $dimensionSpacePointHashToUriPath,
        private readonly Segments $segments,
    )
    {
    }

    public static function create(
        Segments $segments,
        Separator $separator,
        ContentDimensionSourceInterface $contentDimensionSource
    ): self
    {
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

                if ($mappingElement->uriPathSegmentValue === '') {
                    if ($mappingElement->dimensionValue->value !== $segment->defaultDimensionValue) {
                        throw new UriPathResolverConfigurationException('Empty URL Path Segment value is only allowed for the default dimension value "' . $segment->defaultDimensionValue . '" - but you configured it for dimension value "' . $mappingElement->dimensionValue->value . '"');
                    }
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
            $append = array();

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


    public function resolveDimensionSpacePoint(DimensionResolverContext $context): DimensionResolverContext
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
        } elseif (strlen($normalizedUriPath) === 0) {
            // Special case "/"
            $context = $context->withAddedDimensionSpacePoint($this->segments->defaultDimensionSpacePoint());
        }

        return $context;
    }

    public function fromDimensionSpacePointToUriConstraints(DimensionSpacePoint $dimensionSpacePoint, SiteIdentifier $targetSiteIdentifier, UriConstraints $uriConstraints): UriConstraints
    {
        // TODO $dimensionSpacePoint = self::reduceDimensionSpacePointToOnlyIncludedDimensions($dimensionSpacePoint);

        if (isset($this->dimensionSpacePointHashToUriPath[$dimensionSpacePoint->hash])) {
            $uriPath = $this->dimensionSpacePointHashToUriPath[$dimensionSpacePoint->hash];
            if (strlen($uriPath) > 0) {
                return $uriConstraints->withPathPrefix($uriPath . '/', true);
            }
        }

        return $uriConstraints;
    }
}
