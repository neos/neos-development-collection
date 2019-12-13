<?php
namespace Neos\Media\Domain\Strategy;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ResourceManagement\PersistentResource;

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
     * @param PersistentResource $resource
     * @param array $additionalProperties Optional properties that can be taken into account for deciding the model class. what you get here can depend on the caller, so you should always fallback to something based on the resource.
     * @return string the determined target class name
     */
    public function map(PersistentResource $resource, array $additionalProperties = []);
}
