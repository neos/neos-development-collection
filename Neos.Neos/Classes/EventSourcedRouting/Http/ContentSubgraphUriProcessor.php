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

namespace Neos\Neos\EventSourcedRouting\Http;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\Neos\EventSourcedRouting\Http\ContentDimensionLinking\ContentDimensionValueUriProcessorResolver;

/**
 * The default content subgraph URI processor
 *
 * @Flow\Scope("singleton")
 */
final class ContentSubgraphUriProcessor implements ContentSubgraphUriProcessorInterface
{
    /**
     * @Flow\Inject
     * @var ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    /**
     * @Flow\Inject
     * @var ContentDimensionValueUriProcessorResolver
     */
    protected $contentDimensionValueUriProcessorResolver;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos", path="routing.supportEmptySegmentForDimensions")
     * @var boolean
     */
    protected $supportEmptySegmentForDimensions;

    /**
     * @throws ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveDimensionUriConstraints(
        NodeAddress $nodeAddress,
        bool $currentNodeIsSiteNode = false
    ): UriConstraints {
        $uriConstraints = UriConstraints::create();

        if ($nodeAddress->isInLiveWorkspace()) {
            $dimensions = $this->contentDimensionSource->getContentDimensionsOrderedByPriority();
            $this->sortDimensionsByOffset($dimensions);
            $uriPathSegmentOffset = 0;
            $uriPathSegmentConstraints = UriConstraints::create();
            $allUriPathSegmentDetectableDimensionPresetsAreDefault = true;

            $orderedDimensions = $this->contentDimensionSource->getContentDimensionsOrderedByPriority();
            foreach ($orderedDimensions as $rawContentDimensionIdentifier => $contentDimension) {
                $resolutionOptions = $contentDimension->getConfigurationValue('resolution.options') ?? [];
                $resolutionMode = $contentDimension->getConfigurationValue('resolution.mode')
                    ? new BasicContentDimensionResolutionMode(
                        $contentDimension->getConfigurationValue('resolution.mode')
                    ) : null;

                $contentDimensionValue = $contentDimension
                    ->getValue($nodeAddress->dimensionSpacePoint->coordinates[$rawContentDimensionIdentifier]);
                $linkProcessor = $this->contentDimensionValueUriProcessorResolver
                    ->resolveContentDimensionValueUriProcessor($contentDimension);
                if (!is_null($contentDimensionValue)) {
                    if (
                        $resolutionMode !== null
                        && $resolutionMode->getMode()
                            === BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT
                    ) {
                        if (!isset($resolutionOptions['offset'])) {
                            $resolutionOptions['offset'] = $uriPathSegmentOffset;
                        }
                        if ($contentDimensionValue->value !== $contentDimension->defaultValue->value) {
                            $allUriPathSegmentDetectableDimensionPresetsAreDefault = false;
                        }
                        $uriPathSegmentOffset++;
                        $uriPathSegmentConstraints = $linkProcessor->processUriConstraints(
                            $uriPathSegmentConstraints,
                            $contentDimension,
                            $contentDimensionValue,
                            $resolutionOptions
                        );
                    } else {
                        $uriConstraints = $linkProcessor->processUriConstraints(
                            $uriConstraints,
                            $contentDimension,
                            $contentDimensionValue,
                            $resolutionOptions
                        );
                    }
                }
            }

            if (
                (!$this->supportEmptySegmentForDimensions || !$allUriPathSegmentDetectableDimensionPresetsAreDefault)
                && $uriPathSegmentOffset > 0
            ) {
                $uriPathSegmentConstraints = $uriPathSegmentConstraints->withPathPrefix('/', true);
                $uriConstraints = $uriConstraints->merge($uriPathSegmentConstraints);
            }
        }

        return $uriConstraints;
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
