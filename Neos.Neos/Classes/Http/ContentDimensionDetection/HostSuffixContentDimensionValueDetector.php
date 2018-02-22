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
use Neos\Flow\Http;

/**
 * A content dimension preset detector evaluating the host suffix
 */
final class HostSuffixContentDimensionValueDetector implements ContentDimensionValueDetectorInterface
{
    /**
     * @var array
     */
    protected $defaultOptions = [];


    /**
     * @param Dimension\ContentDimension $contentDimension
     * @param Http\Component\ComponentContext $componentContext
     * @param array|null $overrideOptions
     * @return Dimension\ContentDimensionValue|null
     */
    public function detectValue(Dimension\ContentDimension $contentDimension, Http\Component\ComponentContext $componentContext, array $overrideOptions = null): ?Dimension\ContentDimensionValue
    {
        $host = $componentContext->getHttpRequest()->getUri()->getHost();
        $hostLength = mb_strlen($host);
        foreach ($contentDimension->getValues() as $contentDimensionValue) {
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
