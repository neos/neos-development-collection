<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentDimensionLinking;

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
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\BasicContentDimensionResolutionMode;

/**
 * The resolver for content dimension value uri processors
 *
 * @Flow\Scope("singleton")
 */
final class ContentDimensionValueUriProcessorResolver
{
    /**
     * @param Dimension\ContentDimension $contentDimension
     * @return ContentDimensionValueUriProcessorInterface
     * @throws Exception\InvalidContentDimensionValueUriProcessorException
     */
    public function resolveContentDimensionValueUriProcessor(Dimension\ContentDimension $contentDimension): ContentDimensionValueUriProcessorInterface
    {
        $linkProcessorClassName = $contentDimension->getConfigurationValue('resolution.linkProcessorComponent.implementationClassName');
        if ($linkProcessorClassName) {
            if (class_exists($linkProcessorClassName)) {
                $linkProcessor = new $linkProcessorClassName();

                if (!$linkProcessor instanceof ContentDimensionValueUriProcessorInterface) {
                    throw new Exception\InvalidContentDimensionValueUriProcessorException(
                        'Configured content dimension preset link processor "' . $linkProcessorClassName . '" does not implement ' . ContentDimensionValueUriProcessorInterface::class . '. Please check your dimension configuration.',
                        1510839085
                    );
                }

                return $linkProcessor;
            } else {
                throw new Exception\InvalidContentDimensionValueUriProcessorException(
                    'Could not resolve dimension preset detection component for dimension "' . $contentDimension->identifier . '". Class "' . $linkProcessorClassName . '" does not exist. Please check your dimension configuration.',
                    1510839089
                );
            }
        }

        $rawResolutionMode = $contentDimension->getConfigurationValue('resolution.mode');
        $resolutionMode = $rawResolutionMode ? new BasicContentDimensionResolutionMode($rawResolutionMode) : new BasicContentDimensionResolutionMode(BasicContentDimensionResolutionMode::RESOLUTION_MODE_NULL);

        switch ($resolutionMode->getMode()) {
            case BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTPREFIX:
                return new HostPrefixContentDimensionValueUriProcessor();
                break;
            case BasicContentDimensionResolutionMode::RESOLUTION_MODE_HOSTSUFFIX:
                return new HostSuffixContentDimensionValueUriProcessor();
                break;
            case BasicContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT:
                return new UriPathSegmentContentDimensionValueUriProcessor();
            case BasicContentDimensionResolutionMode::RESOLUTION_MODE_NULL:
            default:
                return new NullContentDimensionValueUriProcessor();
        }
    }
}
