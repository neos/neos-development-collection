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
use Psr\Http\Message\ServerRequestInterface;

/**
 * A content dimension preset detector evaluating the host prefix
 */
final class HostPrefixContentDimensionValueDetector implements ContentDimensionValueDetectorInterface
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
        foreach ($contentDimension->values as $contentDimensionValue) {
            $resolutionValue = $contentDimensionValue->getConfigurationValue('resolution.value');
            if ($resolutionValue) {
                if (mb_substr($host, 0, mb_strlen($resolutionValue)) === $resolutionValue) {
                    return $contentDimensionValue;
                }
            } else {
                continue;
            }
        }

        // we leave the decision about how to handle empty values to the detection component
        return null;
    }
}
