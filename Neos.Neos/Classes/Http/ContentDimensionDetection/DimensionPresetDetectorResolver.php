<?php

namespace Neos\Neos\Http\ContentDimensionDetection;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Http\ContentDimensionResolutionMode;
use Neos\Neos\Http\Exception\InvalidDimensionPresetDetectorException;

/**
 * The HTTP component for detecting the requested dimension space point
 *
 * @Flow\Scope("singleton")
 */
final class DimensionPresetDetectorResolver
{
    /**
     * @param string $dimensionName
     * @param array $presetConfiguration
     * @return ContentDimensionPresetDetectorInterface
     * @throws InvalidDimensionPresetDetectorException
     */
    public function resolveDimensionPresetDetector(string $dimensionName, array $presetConfiguration): ContentDimensionPresetDetectorInterface
    {
        if (isset($presetConfiguration['detectionComponent']['implementationClassName'])) {
            if (class_exists($presetConfiguration['detectionComponent']['implementationClassName'])) {
                $detector = new $presetConfiguration['detectionComponent']['implementationClassName']();
                if (!$detector instanceof ContentDimensionPresetDetectorInterface) {
                    throw new InvalidDimensionPresetDetectorException(
                        'Configured content dimension preset detector "' . $presetConfiguration['detectionComponent']['implementationClassName'] . '" does not implement ' . ContentDimensionPresetDetectorInterface::class . '. Please check your dimension configuration.',
                        1510826082
                    );
                }
                return $detector;
            } else {
                throw new InvalidDimensionPresetDetectorException(
                    'Could not resolve dimension preset detection component for dimension "' . $dimensionName . '". Please check your dimension configuration.',
                    1510750184
                );
            }
        }

        $resolutionMode = new ContentDimensionResolutionMode($presetConfiguration['resolutionMode'] ?? ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT);
        switch ($resolutionMode->getMode()) {
            case ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN:
                return new SubdomainDimensionPresetDetector();
            case ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN:
                return new TopLevelDomainDimensionPresetDetector();
            case ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT:
            default:
                return new UriPathSegmentDimensionPresetDetector();
        }
    }
}
