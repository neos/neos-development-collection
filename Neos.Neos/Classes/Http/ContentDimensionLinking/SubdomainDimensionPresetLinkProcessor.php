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
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http;

/**
 * Subdomain based dimension preset detector
 */
final class SubdomainDimensionPresetLinkProcessor implements ContentDimensionPresetLinkProcessorInterface
{
    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $dimensionPresetSource;


    /**
     * @param Http\Uri $baseUri
     * @param string $dimensionName
     * @param array $presetConfiguration
     * @param array $dimensionValues
     */
    public function processDimensionBaseUri(Http\Uri $baseUri, string $dimensionName, array $presetConfiguration, array $dimensionValues)
    {
        $currentValue = null;
        foreach ($presetConfiguration['presets'] as $preset) {
            if (mb_substr($baseUri->getHost(), 0, mb_strlen($preset['detectionValue'])) === $preset['detectionValue']) {
                $currentValue = $preset['detectionValue'];
                break;
            }
        }

        $newValue = $this->dimensionPresetSource->findPresetByDimensionValues($dimensionName, $dimensionValues)['detectionValue'];

        $baseUri->setHost($newValue . mb_substr($baseUri->getHost(), mb_strlen($currentValue)));
    }
}
