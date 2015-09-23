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
use TYPO3\Flow\Resource\Resource;

/**
 * Describes a strategy to find an asset model class based on the resource and optional source properties.
 *
 */
interface AssetModelMappingStrategyInterface
{
    /**
     * Map the given resource to a media model class.
     * MUST always return a fully qualified class name for a media model. If you need to fallback to different strategies you need to implement a "ConjunctionStrategy", but in the end you have to return a final class name.
     *
     * @param Resource $resource
     * @param array $additionalProperties Optional properties that can be taken into account for deciding the model class. what you get here can depend on the caller, so you should always fallback to something based on the resource.
     * @return string the determined target class name
     */
    public function map(Resource $resource, array $additionalProperties = array());
}
