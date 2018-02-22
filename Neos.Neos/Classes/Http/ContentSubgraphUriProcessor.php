<?php
namespace Neos\Neos\Http;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\Domain\Context\Content\ContentQuery;
use Neos\Neos\Http\ContentDimensionLinking\ContentDimensionValueUriProcessorResolver;

/**
 * The default content subgraph URI processor
 */
final class ContentSubgraphUriProcessor implements ContentSubgraphUriProcessorInterface
{
    /**
     * @Flow\Inject
     * @var Dimension\ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    /**
     * @Flow\Inject
     * @var ContentDimensionValueUriProcessorResolver
     */
    protected $contentDimensionValueUriProcessorResolver;

    /**
     * @Flow\InjectConfiguration("routing.supportEmptySegmentForDimensions")
     * @var boolean
     */
    protected $supportEmptySegmentForDimensions;

    /**
     * @param ContentQuery $contentQuery
     * @param bool $currentNodeIsSiteNode
     * @return UriConstraints
     * @throws Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveDimensionUriConstraints(ContentQuery $contentQuery, bool $currentNodeIsSiteNode = false): UriConstraints
    {
        $uriConstraints = UriConstraints::create();

        if ($contentQuery->getWorkspaceName()->isLive()) {
            $dimensions = $this->contentDimensionSource->getContentDimensionsOrderedByPriority();
            $this->sortDimensionsByOffset($dimensions);
            $uriPathSegmentOffset = 0;
            $uriPathSegmentConstraints = UriConstraints::create();
            $allUriPathSegmentDetectableDimensionPresetsAreDefault = true;

            foreach ($this->contentDimensionSource->getContentDimensionsOrderedByPriority() as $rawContentDimensionIdentifier => $contentDimension) {
                $resolutionOptions = $contentDimension->getConfigurationValue('resolution.options') ?? [];
                $resolutionMode = $contentDimension->getConfigurationValue('resolution.mode')
                    ? new BasicContentDimensionResolutionMode($contentDimension->getConfigurationValue('resolution.mode'))
                    : null;

                $contentDimensionValue = $contentDimension->getValue($contentQuery->getDimensionSpacePoint()->getCoordinates()[$rawContentDimensionIdentifier]);
                $linkProcessor = $this->contentDimensionValueUriProcessorResolver->resolveContentDimensionLinkProcessor($contentDimension);
                if ($resolutionMode->getMode() === BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT) {
                    if (!isset($resolutionOptions['offset'])) {
                        $resolutionOptions['offset'] = $uriPathSegmentOffset;
                    }
                    if ($contentDimensionValue !== $contentDimension->getDefaultValue()) {
                        $allUriPathSegmentDetectableDimensionPresetsAreDefault = false;
                    }
                    $uriPathSegmentOffset++;
                    $uriPathSegmentConstraints = $linkProcessor->processUriConstraints($uriPathSegmentConstraints, $contentDimension, $contentDimensionValue, $resolutionOptions);
                } else {
                    $uriConstraints = $linkProcessor->processUriConstraints($uriConstraints, $contentDimension, $contentDimensionValue, $resolutionOptions);
                }
            }

            if ((!$this->supportEmptySegmentForDimensions || !$allUriPathSegmentDetectableDimensionPresetsAreDefault)
                && $uriPathSegmentOffset > 0) {
                $uriConstraints = $uriConstraints->merge($uriPathSegmentConstraints);
                if ($currentNodeIsSiteNode) {
                    $uriConstraints = $uriConstraints->withPathPrefix('/', true);
                }
            }
        }

        return $uriConstraints;
    }

    /**
     * @param array|Dimension\ContentDimension[] $dimensions
     * @return void
     */
    protected function sortDimensionsByOffset(array & $dimensions)
    {
        uasort($dimensions, function (Dimension\ContentDimension $dimensionA, Dimension\ContentDimension $dimensionB) {
            return ($dimensionA->getConfigurationValue('resolution.options.offset') ?: 0)
                <=> ($dimensionB->getConfigurationValue('resolution.options.offset') ?: 0);
        });
    }
}
