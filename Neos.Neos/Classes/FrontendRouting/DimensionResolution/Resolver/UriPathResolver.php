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

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimension;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverContext;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;
use Webmozart\Assert\Assert;

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
 */
final class UriPathResolver implements DimensionResolverInterface
{

    private UriPathSegmentBasedOptions $options;


    public function resolveDimensionSpacePoint(DimensionResolverContext $context): DimensionResolverContext
    {
        $this->validate();


        foreach ($this->contentDimension->getValues() as $contentDimensionValue) {
            $resolutionValue = $contentDimensionValue->getConfigurationValue('meta.uriRepresentation') ?? $contentDimensionValue->getValue();
            if ($resolutionValue !== '' && strpos($context->remainingUriPath(), $resolutionValue) === 0) {
                return $context
                    ->withRemainingUriPath(ltrim(substr($context->remainingUriPath(), strlen($resolutionValue)), '/'))
                    ->addDimensionSpacePointCoordinate($this->contentDimension->getIdentifier(), $contentDimensionValue);
            }
        }
        return $context->addDimensionSpacePointCoordinate($this->contentDimension->getIdentifier(), $this->contentDimension->getDefaultValue());
    }

    public function resolveDimensionUriConstraints(UriConstraints $uriConstraints, DimensionSpacePoint $dimensionSpacePoint): UriConstraints
    {
        $dimensionCoordinate = $dimensionSpacePoint->getCoordinate($this->contentDimension->getIdentifier());
        $contentDimensionValue = $dimensionCoordinate !== null ? $this->contentDimension->getValue($dimensionCoordinate) : $this->contentDimension->getDefaultValue();
        if ($contentDimensionValue === null) {
            // TODO throw exception
        }
        $resolutionValue = $contentDimensionValue->getConfigurationValue('meta.uriRepresentation') ?? $contentDimensionValue->getValue();
        if ($resolutionValue === '') {
            return $uriConstraints;
        }
        return $uriConstraints->withPathPrefix($resolutionValue . '/', true);
    }

    private function validate()
    {
        foreach ($this->options->segments as $segment) {
            $contentDimension = $this->contentDimensionSource->getDimension($segment->dimensionIdentifier);
            if ($contentDimension === null) {
                throw new \RuntimeException('TODO: Content Dimension "' . $contentDimension . '" is not configured.');
            }

            foreach ($segment->uriPathSegmentMapping as $mappingElement) {
                // TODO: ExistsValue??
                if ($contentDimension->getValue($mappingElement->contentDimensionValue->value) === null) {
                    throw new \RuntimeException('TODO: Content Dimension Value "' . $mappingElement->contentDimensionValue->value . '" not configured');
                }
            }
        }
    }

}
