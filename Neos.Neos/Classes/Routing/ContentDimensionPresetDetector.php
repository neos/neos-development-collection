<?php

namespace Neos\Neos\Routing;

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
use Neos\Neos\Routing\Exception\NoSuchDimensionValueException;

/**
 * The default implementation for detecting the requested dimension preset
 */
final class ContentDimensionPresetDetector implements ContentDimensionPresetDetectorInterface
{
    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $dimensionPresetSource;

    /**
     * @todo make configurable
     * @var string
     */
    protected $pathSegmentDimensionDelimiter = '_';


    /**
     * @inheritdoc
     * @todo declare detection components, use them respecting the given dimensions reduce the host piece by piece
     * @todo check if combination is allowed
     */
    public function extractDimensionValues(Http\Uri $uri, string & $requestPath, bool $allowEmptyValues = false): array
    {
        $dimensionValues = [];

        if (!empty($requestPath)) {
            $pathSegments = explode('/', ($requestPath));
            $pathSegmentValues = explode($this->pathSegmentDimensionDelimiter, $pathSegments[0]);
        } else {
            $pathSegmentValues = [];
        }

        $pathSegmentNumber = 0;
        $pathSegmentUsed = false;
        foreach ($this->dimensionPresetSource->getAllPresets() as $dimensionName => $contentDimension) {
            $detectionMode = $contentDimension['detectionMode'] ?? self::DETECTION_MODE_URIPATHSEGMENT;

            switch ($detectionMode) {
                case self::DETECTION_MODE_SUBDOMAIN:
                    $defaultPreset = null;
                    if ($allowEmptyValues && isset($contentDimension['defaultPreset'])) {
                        $defaultPreset = $contentDimension['presets'][$contentDimension['defaultPreset']];
                    }
                    $preset = $this->getMatchingPresetForSubdomain($contentDimension['presets'], $uri->getHost(), $defaultPreset, $dimensionName);
                    $dimensionValues[$dimensionName] = $preset['values'];
                    break;
                case self::DETECTION_MODE_DOMAINNAME:
                    $preset = $this->getMatchingPresetForDomainName($contentDimension['presets'], $uri->getHost(), null, $dimensionName);
                    $dimensionValues[$dimensionName] = $preset['values'];
                    break;
                case self::DETECTION_MODE_TOPLEVELDOMAIN:
                    $preset = $this->getMatchingPresetForTopLevelDomain($contentDimension['presets'], $uri->getHost(), null, $dimensionName);
                    $dimensionValues[$dimensionName] = $preset['values'];
                    break;
                case self::DETECTION_MODE_URIPATHSEGMENT:
                default:
                    if (isset($pathSegmentValues[$pathSegmentNumber])) {
                        $preset = $this->getMatchingPresetForPathSegment($contentDimension['presets'], $pathSegmentValues[$pathSegmentNumber], $dimensionName);
                        $dimensionValues[$dimensionName] = $preset['values'];
                        $pathSegmentNumber++;
                        $pathSegmentUsed = true;
                    } elseif ($allowEmptyValues) {
                        $dimensionValues[$dimensionName] = $contentDimension['presets'][$contentDimension['defaultPreset']]['values'];
                        // @todo handle misconfiguration
                    } else {
                        // @todo throw exception
                    }
            }
        }

        if ($pathSegmentUsed) {
            unset($pathSegments[0]);
            $requestPath = implode('/', $pathSegments);
        }

        return $dimensionValues;
    }


    /**
     * @param array $presets
     * @param string $host
     * @param array $fallbackPreset
     * @param string $dimensionName
     * @return array
     * @throws NoSuchDimensionValueException
     */
    protected function getMatchingPresetForSubdomain(array $presets, string $host, array $fallbackPreset = null, string $dimensionName): array
    {
        foreach ($presets as $preset) {
            $valueLength = mb_strlen($preset['detectionValue']);

            if (mb_substr($host, 0, $valueLength) === $preset['detectionValue']) {
                return $preset;
            }
        }

        if ($fallbackPreset) {
            return $fallbackPreset;
        }

        throw new NoSuchDimensionValueException(sprintf('Could not find a preset for content dimension "%s" by the top domain name of the given host "%s".', $dimensionName, $host), 1507726998);
    }

    /**
     * @param array $presets
     * @param string $host
     * @param array $fallbackPreset
     * @param string $dimensionName
     * @return array
     * @throws NoSuchDimensionValueException
     */
    protected function getMatchingPresetForDomainName(array $presets, string $host, array $fallbackPreset = null, string $dimensionName): array
    {
        foreach ($presets as $preset) {
            if (mb_strpos($host, $preset['detectionValue']) !== false) {
                return $preset;
            }
        }

        if ($fallbackPreset) {
            return $fallbackPreset;
        }

        throw new NoSuchDimensionValueException(sprintf('Could not find a preset for content dimension "%s" by the top domain name of the given host "%s".', $dimensionName, $host), 1507726998);
    }

    /**
     * @param array $presets
     * @param string $host
     * @param array $fallbackPreset
     * @param string $dimensionName
     * @return array
     * @throws NoSuchDimensionValueException
     */
    protected function getMatchingPresetForTopLevelDomain(array $presets, string $host, array $fallbackPreset = null, string $dimensionName): array
    {
        $hostLength = mb_strlen($host);
        foreach ($presets as $preset) {
            $pivot = $hostLength - mb_strlen($preset['detectionValue']);

            if (mb_substr($host, $pivot) === $preset['detectionValue']) {
                return $preset;
            }
        }

        if ($fallbackPreset) {
            return $fallbackPreset;
        }

        throw new NoSuchDimensionValueException(sprintf('Could not find a preset for content dimension "%s" by the top level domain of the given host "%s".', $dimensionName, $host), 1507726985);
    }

    /**
     * @param array $presets
     * @param string $pathSegment
     * @param array $fallbackPreset
     * @param string $dimensionName
     * @return array
     * @throws NoSuchDimensionValueException
     */
    protected function getMatchingPresetForPathSegment(array $presets, string $pathSegment, array $fallbackPreset = null, string $dimensionName): array
    {
        foreach ($presets as $preset) {
            $detectionValue = $preset['detectionValue'] ?? $preset['uriSegment'];
            if ($pathSegment === $detectionValue) {
                return $preset;
            }
        }

        if ($fallbackPreset) {
            return $fallbackPreset;
        }

        throw new NoSuchDimensionValueException(sprintf('Could not find a preset for content dimension "%s" by the given URI segment "%s".', $dimensionName, $uriSegment), 1413389321);
    }
}
