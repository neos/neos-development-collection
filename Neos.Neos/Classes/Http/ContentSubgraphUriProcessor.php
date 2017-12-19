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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Http\ContentDimensionLinking\DimensionPresetLinkProcessorResolver;

/**
 * The default content subgraph URI processor
 */
final class ContentSubgraphUriProcessor implements ContentSubgraphUriProcessorInterface
{
    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $dimensionPresetSource;

    /**
     * @Flow\Inject
     * @var DimensionPresetLinkProcessorResolver
     */
    protected $dimensionPresetLinkProcessorResolver;

    /**
     * @Flow\InjectConfiguration("routing.supportEmptySegmentForDimensions")
     * @var boolean
     */
    protected $supportEmptySegmentForDimensions;

    /**
     * @param NodeInterface $node
     * @return UriConstraints
     * @throws Exception\InvalidDimensionPresetLinkProcessorException
     */
    public function resolveDimensionUriConstraints(NodeInterface $node): UriConstraints
    {
        $uriConstraints = UriConstraints::create();

        /** @var ContentContext $contentContext */
        $contentContext = $node->getContext();

        if (!$contentContext->isInBackend()) {
            $presets = $this->dimensionPresetSource->getAllPresets();
            $dimensionValues = $node->getContext()->getDimensions();
            $this->sortDimensionValuesByOffset($dimensionValues, $presets);
            $uriPathSegmentOffset = 0;
            $uriPathSegmentConstraints = UriConstraints::create();
            $allUriPathSegmentDetectableDimensionPresetsAreDefault = true;

            foreach ($presets as $dimensionName => $presetConfiguration) {
                $options = $presetConfiguration['resolution']['options'] ?? [];
                $resolutionMode = new ContentDimensionResolutionMode(
                    $presetConfiguration['resolution']['mode']
                    ?? ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT
                );

                if (isset($dimensionValues[$dimensionName])) {
                    $preset = $this->dimensionPresetSource->findPresetByDimensionValues($dimensionName, $dimensionValues[$dimensionName]);
                } else {
                    $preset = $presetConfiguration['presets'][$presetConfiguration['defaultPreset']];
                    $preset['identifier'] = $presetConfiguration['defaultPreset'];
                }

                $linkProcessor = $this->dimensionPresetLinkProcessorResolver->resolveDimensionPresetLinkProcessor($dimensionName, $presetConfiguration);
                if ($resolutionMode->getMode() === ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT) {
                    if (!isset($options['offset'])) {
                        $options['offset'] = $uriPathSegmentOffset;
                    }

                    if ($presetConfiguration['defaultPreset'] !== $preset['identifier']) {
                        $allUriPathSegmentDetectableDimensionPresetsAreDefault = false;
                    }
                    $uriPathSegmentOffset++;
                    $uriPathSegmentConstraints = $linkProcessor->processUriConstraints($uriPathSegmentConstraints, $dimensionName, $presetConfiguration, $preset, $options);
                } else {
                    $uriConstraints = $linkProcessor->processUriConstraints($uriConstraints, $dimensionName, $presetConfiguration, $preset, $options);
                }
            }

            if ((!$this->supportEmptySegmentForDimensions || !$allUriPathSegmentDetectableDimensionPresetsAreDefault)
                && $uriPathSegmentOffset > 0) {
                $uriConstraints = $uriConstraints->merge($uriPathSegmentConstraints);
                if ($node->getParentPath() !== SiteService::SITES_ROOT_PATH) {
                    $uriConstraints = $uriConstraints->withPathPrefix('/', true);
                }
            }
        }

        return $uriConstraints;
    }

    /**
     * @param array & $dimensionValues
     * @param array $presets
     * @return void
     */
    protected function sortDimensionValuesByOffset(array & $dimensionValues, array $presets)
    {
        uksort($dimensionValues, function ($dimensionNameA, $dimensionNameB) use ($presets) {
            if (isset($presets[$dimensionNameA]['resolution']['options']['offset'])
                && isset($presets[$dimensionNameB]['resolution']['options']['offset'])) {
                return $presets[$dimensionNameA]['resolution']['options']['offset'] <=> $presets[$dimensionNameB]['resolution']['options']['offset'];
            }

            return 0;
        });
    }
}
