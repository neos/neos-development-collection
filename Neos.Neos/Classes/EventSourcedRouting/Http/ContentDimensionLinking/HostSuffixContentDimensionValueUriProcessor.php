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

use Neos\Flow\Mvc\Routing;
use Neos\ContentRepository\DimensionSpace\Dimension;

/**
 * Host suffix based content dimension value uri processor
 */
final class HostSuffixContentDimensionValueUriProcessor implements ContentDimensionValueUriProcessorInterface
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
        $hostSuffixesToBeReplaced = [];
        foreach ($contentDimension->values as $availableDimensionValue) {
            $resolutionValue = $availableDimensionValue->getConfigurationValue('resolution.value');
            if ($resolutionValue) {
                $hostSuffixesToBeReplaced[] = $resolutionValue;
            }
        }

        return $uriConstraints->withHostSuffix(
            $contentDimensionValue->getConfigurationValue('resolution.value') ?: '',
            $hostSuffixesToBeReplaced
        );
    }
}
