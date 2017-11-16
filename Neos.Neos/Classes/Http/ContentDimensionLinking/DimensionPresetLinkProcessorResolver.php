<?php

namespace Neos\Neos\Http\ContentDimensionLinking;

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
use Neos\Neos\Http\Exception\InvalidDimensionPresetLinkProcessorException;

/**
 * The resolver for dimension preset link processors
 *
 * @Flow\Scope("singleton")
 */
final class DimensionPresetLinkProcessorResolver
{
    /**
     * @param string $dimensionName
     * @param array $presetConfiguration
     * @return ContentDimensionPresetLinkProcessorInterface
     * @throws InvalidDimensionPresetLinkProcessorException
     */
    public function resolveDimensionPresetLinkProcessor(string $dimensionName, array $presetConfiguration): ContentDimensionPresetLinkProcessorInterface
    {
        if (isset($presetConfiguration['linkProcessorComponent']['implementationClassName'])) {
            if (class_exists($presetConfiguration['linkProcessorComponent']['implementationClassName'])) {
                $linkProcessor = new $presetConfiguration['linkProcessorComponent']['implementationClassName']();
                if (!$linkProcessor instanceof ContentDimensionPresetLinkProcessorInterface) {
                    throw new InvalidDimensionPresetLinkProcessorException(
                        'Configured content dimension preset link processor "' . $presetConfiguration['linkProcessorComponent']['implementationClassName'] . '" does not implement ' . ContentDimensionPresetLinkProcessorInterface::class . '. Please check your dimension configuration.',
                        1510839085
                    );
                }

                return $linkProcessor;
            } else {
                throw new InvalidDimensionPresetLinkProcessorException(
                    'Could not resolve dimension preset detection component for dimension "' . $dimensionName . '". Class "' . $presetConfiguration['linkProcessorComponent']['implementationClassName'] . '" does not exist. Please check your dimension configuration.',
                    1510839089
                );
            }
        }

        $resolutionMode = new ContentDimensionResolutionMode($presetConfiguration['resolutionMode'] ?? ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT);

        switch ($resolutionMode->getMode()) {
            case ContentDimensionResolutionMode::RESOLUTION_MODE_SUBDOMAIN:
                return new SubdomainDimensionPresetLinkProcessor();
                break;
            case ContentDimensionResolutionMode::RESOLUTION_MODE_TOPLEVELDOMAIN:
                return new TopLevelDomainDimensionPresetLinkProcessor();
                break;
            case ContentDimensionResolutionMode::RESOLUTION_MODE_URIPATHSEGMENT:
            default:
                return new UriPathSegmentDimensionPresetLinkProcessor();
        }
    }
}
