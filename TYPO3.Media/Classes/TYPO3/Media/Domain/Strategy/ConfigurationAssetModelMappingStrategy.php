<?php
namespace TYPO3\Media\Domain\Strategy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\MediaTypes;
use TYPO3\Flow\Utility\PositionalArraySorter;

/**
 * A mapping strategy based on configured expressions.
 *
 * @Flow\Scope("singleton")
 */
class ConfigurationAssetModelMappingStrategy implements AssetModelMappingStrategyInterface
{
    /**
     * @Flow\InjectConfiguration(package="TYPO3.Media", path="asset.modelMappingStrategy")
     * @var array
     */
    protected $settings;

    /**
     *
     */
    public function initializeObject()
    {
        $strategyConfigurationSorter = new PositionalArraySorter($this->settings['patterns']);
        $this->settings['patterns'] = $strategyConfigurationSorter->toArray();
    }

    /**
     * Map the given resource to a media model class.
     *
     * @param Resource $resource
     * @param array $additionalProperties Optional properties that can be taken into account for deciding the model class. what you get here can depend on the caller, so you should always fallback to something based on the resource.
     * @return string
     */
    public function map(Resource $resource, array $additionalProperties = array())
    {
        $mediaType = MediaTypes::getMediaTypeFromFilename($resource->getFilename());
        foreach ($this->settings['patterns'] as $pattern => $mappingInformation) {
            if (preg_match($pattern, $mediaType)) {
                return $mappingInformation['className'];
            }
        }

        return $this->settings['default'];
    }
}
