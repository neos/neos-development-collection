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

use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Neos\Http\ContentDimensionDetection\DimensionPresetDetectorResolver;

/**
 * The HTTP component for detecting the requested dimension space point
 */
final class DetectContentSubgraphComponent implements Http\Component\ComponentInterface
{
    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $dimensionPresetSource;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var DimensionPresetDetectorResolver
     */
    protected $contentDimensionPresetDetectorResolver;

    /**
     * @Flow\InjectConfiguration(path="routing.supportEmptySegmentForDimensions")
     * @var boolean
     */
    protected $allowEmptyPathSegments;

    /**
     * @Flow\InjectConfiguration(path="contentDimensions.resolution.uriPathSegmentDelimiter")
     * @var string
     */
    protected $uriPathSegmentDelimiter;

    /**
     * @param Http\Component\ComponentContext $componentContext
     * @throws Exception\InvalidDimensionPresetDetectorException
     */
    public function handle(Http\Component\ComponentContext $componentContext)
    {
        $uriPathSegmentUsed = false;
        $dimensionValues = $this->detectDimensionSpacePoint($componentContext, $uriPathSegmentUsed);
        $workspaceName = $this->detectContentStream($componentContext);


        $existingParameters = $componentContext->getParameter(RoutingComponent::class, 'parameters') ?? RouteParameters::createEmpty();
        $parameters = $existingParameters
            ->withParameter('dimensionValues', json_encode($dimensionValues))
            ->withParameter('workspaceName', $workspaceName)
            ->withParameter('uriPathSegmentUsed', $uriPathSegmentUsed);
        $componentContext->setParameter(RoutingComponent::class, 'parameters', $parameters);
    }

    /**
     * @param Http\Component\ComponentContext $componentContext
     * @param bool $uriPathSegmentUsed
     * @return array
     * @throws Exception\InvalidDimensionPresetDetectorException
     */
    protected function detectDimensionSpacePoint(Http\Component\ComponentContext $componentContext, bool &$uriPathSegmentUsed): array
    {
        $coordinates = [];
        $path = $componentContext->getHttpRequest()->getUri()->getPath();

        $isContextPath = NodePaths::isContextPath($path);
        $backendUriDimensionPresetDetector = new ContentDimensionDetection\BackendUriDimensionPresetDetector();
        $presets = $this->dimensionPresetSource->getAllPresets();
        $this->sortPresetsByOffset($presets);
        $uriPathSegmentOffset = 0;
        foreach ($presets as $dimensionName => $presetConfiguration) {
            $detector = $this->contentDimensionPresetDetectorResolver->resolveDimensionPresetDetector($dimensionName, $presetConfiguration);

            $options = $presetConfiguration['resolution']['options'] ?? $this->generateOptionsFromLegacyConfiguration($presetConfiguration, $uriPathSegmentOffset);
            $options['defaultPresetIdentifier'] = $presetConfiguration['defaultPreset'];

            if ($isContextPath) {
                $preset = $backendUriDimensionPresetDetector->detectPreset($dimensionName, $presetConfiguration['presets'], $componentContext);
                if ($preset) {
                    $coordinates[$dimensionName] = $preset['values'];
                    if ($detector instanceof ContentDimensionDetection\UriPathSegmentDimensionPresetDetector) {
                        // we might have to remove the uri path segment anyway
                        $uriPathSegmentPreset = $detector->detectPreset($dimensionName, $presetConfiguration['presets'], $componentContext, $options);
                        if ($uriPathSegmentPreset) {
                            $uriPathSegmentUsed = true;
                        }
                    }
                    continue;
                }
            }

            $resolutionMode = $presetConfiguration['resolution']['mode'] ?? ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT;
            if ($resolutionMode === ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT) {
                $options['delimiter'] = $this->uriPathSegmentDelimiter;
            }
            $preset = $detector->detectPreset($dimensionName, $presetConfiguration['presets'], $componentContext, $options);
            if ($preset && $resolutionMode === ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT) {
                $uriPathSegmentUsed = true;
                $uriPathSegmentOffset++;
            }
            if (!$preset && $options && isset($options['allowEmptyValue']) && $options['allowEmptyValue']) {
                if (isset($options['defaultPresetIdentifier']) && $options['defaultPresetIdentifier'] && isset($presetConfiguration['presets'][$options['defaultPresetIdentifier']])) {
                    $preset = $presetConfiguration['presets'][$options['defaultPresetIdentifier']];
                }
            }
            if ($preset) {
                $coordinates[$dimensionName] = $preset['values'];
            }
        }

        return $coordinates;
    }

    /**
     * @param array $presets
     * @return void
     */
    protected function sortPresetsByOffset(array & $presets)
    {
        uasort($presets, function ($presetA, $presetB) use ($presets) {
            if (isset($presetA['resolution']['options']['offset'])
                && isset($presetB['resolution']['options']['offset'])) {
                return $presetA['resolution']['options']['offset'] <=> $presetB['resolution']['options']['offset'];
            }

            return 0;
        });
    }

    /**
     * @todo remove once legacy configuration is removed (probably with 4.0)
     * @param array $presetConfiguration
     * @param int $uriPathSegmentOffset
     * @return array|null
     */
    protected function generateOptionsFromLegacyConfiguration(array $presetConfiguration, int $uriPathSegmentOffset)
    {
        $options = null;

        $resolutionMode = $presetConfiguration['resolution']['mode'] ?? ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT;
        if ($resolutionMode === ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT) {
            $options = [];
            if (!isset($options['offset'])) {
                $options['offset'] = $uriPathSegmentOffset;
            }
            if ($this->allowEmptyPathSegments) {
                $options['allowEmptyValue'] = true;
            } else {
                $options['allowEmptyValue'] = false;
            }
        }

        return $options;
    }

    /**
     * @param Http\Component\ComponentContext $componentContext
     * @return string
     */
    protected function detectContentStream(Http\Component\ComponentContext $componentContext): string
    {
        $contentStreamIdentifier = 'live';

        $requestPath = $componentContext->getHttpRequest()->getUri()->getPath();
        $requestPath = mb_substr($requestPath, mb_strrpos($requestPath, '/') + 1);
        if ($requestPath !== '' && NodePaths::isContextPath($requestPath)) {
                $nodePathAndContext = NodePaths::explodeContextPath($requestPath);
            try {
                $contentStreamIdentifier = $nodePathAndContext['workspaceName'];
            } catch (\InvalidArgumentException $exception) {
            }
        }

        return $contentStreamIdentifier;
    }
}
