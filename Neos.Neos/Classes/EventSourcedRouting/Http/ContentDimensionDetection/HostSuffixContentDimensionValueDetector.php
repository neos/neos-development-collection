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

namespace Neos\Neos\EventSourcedRouting\Http\ContentDimensionDetection;

use Neos\ContentRepository\DimensionSpace\Dimension;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A content dimension preset detector evaluating the host suffix
 */
final class HostSuffixContentDimensionValueDetector implements ContentDimensionValueDetectorInterface
{
    /**
     * @param array<string,mixed>|null $overrideOptions
     */
    public function detectValue(
        Dimension\ContentDimension $contentDimension,
        ServerRequestInterface $request,
        ?array $overrideOptions = null
    ): ?Dimension\ContentDimensionValue {
        $host = $request->getUri()->getHost();
        $hostLength = mb_strlen($host);
        foreach ($contentDimension->values as $contentDimensionValue) {
            $resolutionValue = $contentDimensionValue->getConfigurationValue('resolution.value');
            $pivot = $hostLength - mb_strlen($resolutionValue);

            if (mb_substr($host, $pivot) === $resolutionValue) {
                return $contentDimensionValue;
            }
        }

        // we leave the decision about how to handle empty values to the detection component
        return null;
    }
}
