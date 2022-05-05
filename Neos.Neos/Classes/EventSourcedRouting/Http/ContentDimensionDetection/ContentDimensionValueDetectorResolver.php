<?php
declare(strict_types=1);
namespace Neos\Neos\EventSourcedRouting\Http\ContentDimensionDetection;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\EventSourcedRouting\Http\BasicContentDimensionResolutionMode;

/**
 * The resolver for content dimension value detectors
 *
 * @Flow\Scope("singleton")
 */
final class ContentDimensionValueDetectorResolver
{
    /**
     * @param Dimension\ContentDimension $contentDimension
     * @return ContentDimensionValueDetectorInterface
     * @throws Exception\InvalidContentDimensionValueDetectorException
     */
    public function resolveContentDimensionValueDetector(
        Dimension\ContentDimension $contentDimension
    ): ContentDimensionValueDetectorInterface {
        $detectorClassName = $contentDimension->getConfigurationValue(
            'resolution.detectionComponent.implementationClassName'
        );
        if ($detectorClassName) {
            if (class_exists($detectorClassName)) {
                $detector = new $detectorClassName();
                if (!$detector instanceof ContentDimensionValueDetectorInterface) {
                    throw new Exception\InvalidContentDimensionValueDetectorException(
                        '"' . $detectorClassName
                            . '", configured as content dimension value detector for content dimension "'
                            . $contentDimension->identifier . '", does not implement '
                            . ContentDimensionValueDetectorInterface::class
                            . '. Please check your dimension configuration.',
                        1510826082
                    );
                }
                return $detector;
            } else {
                throw new Exception\InvalidContentDimensionValueDetectorException(
                    'Could not resolve dimension preset detection component for dimension "'
                        . $contentDimension->identifier . '". Please check your dimension configuration.',
                    1510750184
                );
            }
        }

        $resolutionMode = new BasicContentDimensionResolutionMode(
            $contentDimension->getConfigurationValue('resolution.mode')
                ?: BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT
        );
        return match ($resolutionMode->getMode()) {
            BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTPREFIX
                => new HostPrefixContentDimensionValueDetector(),
            BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTSUFFIX
                => new HostSuffixContentDimensionValueDetector(),
            default => new UriPathSegmentContentDimensionValueDetector(),
        };
    }
}
