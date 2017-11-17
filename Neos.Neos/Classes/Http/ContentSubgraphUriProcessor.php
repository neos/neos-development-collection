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
use Neos\Flow\Http;
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
     * @param Http\Uri $currentBaseUri
     * @param NodeInterface $node
     * @return Http\Uri
     */
    public function resolveDimensionBaseUri(Http\Uri $currentBaseUri, NodeInterface $node): Http\Uri
    {
        $baseUri = clone $currentBaseUri;
        if ($node->getContext()->isInBackend()) {
            return $baseUri;
        }

        $presets = $this->dimensionPresetSource->getAllPresets();
        $allUriPathSegmentDetectableDimensionPresetsAreDefault = true;
        $dimensionValues = $node->getContext()->getDimensions();

        $this->sortDimensionValuesByOffset($dimensionValues, $presets);

        foreach ($dimensionValues as $dimensionName => $values) {
            $presetConfiguration = $presets[$dimensionName];
            $preset = $this->dimensionPresetSource->findPresetByDimensionValues($dimensionName, $values);

            $resolutionMode = new ContentDimensionResolutionMode(
                $presetConfiguration['resolution']['mode']
                ?? ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT
            );
            if ($resolutionMode->getMode() === ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT
                && $presetConfiguration['defaultPreset'] !== $preset['identifier']) {
                $allUriPathSegmentDetectableDimensionPresetsAreDefault = false;
            }

            $linkProcessor = $this->dimensionPresetLinkProcessorResolver->resolveDimensionPresetLinkProcessor($dimensionName, $presetConfiguration);
            $linkProcessor->processDimensionBaseUri($baseUri, $dimensionName, $presetConfiguration, $preset);
        }

        if ($this->supportEmptySegmentForDimensions
            && $allUriPathSegmentDetectableDimensionPresetsAreDefault
            && $node->getParentPath() === SiteService::SITES_ROOT_PATH) {
            $baseUri->setPath('/');
        }

        return $baseUri;
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
