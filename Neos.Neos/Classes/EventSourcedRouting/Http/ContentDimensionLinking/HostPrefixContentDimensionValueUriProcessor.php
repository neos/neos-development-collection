<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\EventSourcedRouting\Http\ContentDimensionLinking;

use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\Flow\Mvc\Routing;

/**
 * Host prefix based dimension preset link processor
 */
final class HostPrefixContentDimensionValueUriProcessor implements ContentDimensionValueUriProcessorInterface
{
    /**
     * @param array<string,mixed>|null $overrideOptions
     */
    public function processUriConstraints(
        Routing\Dto\UriConstraints $uriConstraints,
        Dimension\ContentDimension $contentDimension,
        Dimension\ContentDimensionValue $contentDimensionValue,
        ?array $overrideOptions = null
    ): Routing\Dto\UriConstraints {
        $prefixesToBeReplaced = [];
        foreach ($contentDimension->values as $availableContentDimensionValue) {
            $resolutionValue = $availableContentDimensionValue->getConfigurationValue('resolution.value');
            if ($resolutionValue) {
                $prefixesToBeReplaced[] = $resolutionValue;
            }
        }

        return $uriConstraints->withHostPrefix(
            $contentDimensionValue->getConfigurationValue('resolution.value') ?: '',
            $prefixesToBeReplaced
        );
    }
}
