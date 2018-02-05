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

use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Http\BasicContentDimensionResolutionMode;

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
     * @throws Exception\InvalidDimensionValueDetectorException
     */
    public function resolveContentDimensionValueDetector(Dimension\ContentDimension $contentDimension): ContentDimensionValueDetectorInterface
    {
        $detectorClassName = $contentDimension->getConfigurationValue('detectionComponent.implementationClassName');
        if ($detectorClassName) {
            if (class_exists($detectorClassName)) {
                $detector = new $detectorClassName();
                if (!$detector instanceof ContentDimensionValueDetectorInterface) {
                    throw new Exception\InvalidDimensionValueDetectorException(
                        '"' . $detectorClassName . '", configured as content dimension value detector for content dimension "' . $contentDimension->getIdentifier() . '", does not implement ' . ContentDimensionValueDetectorInterface::class . '. Please check your dimension configuration.',
                        1510826082
                    );
                }
                return $detector;
            } else {
                throw new Exception\InvalidDimensionValueDetectorException(
                    'Could not resolve dimension preset detection component for dimension "' . $contentDimension->getIdentifier() . '". Please check your dimension configuration.',
                    1510750184
                );
            }
        }

        $resolutionMode = new BasicContentDimensionResolutionMode($contentDimension->getConfigurationValue('resolution.mode') ?: BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT);
        switch ($resolutionMode->getMode()) {
            case BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTPREFIX:
                return new HostPrefixContentDimensionValueDetector();
            case BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTSUFFIX:
                return new HostSuffixContentDimensionValueDetector();
            case BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT:
            default:
                return new UriPathSegmentContentDimensionValueDetector();
        }
    }
}