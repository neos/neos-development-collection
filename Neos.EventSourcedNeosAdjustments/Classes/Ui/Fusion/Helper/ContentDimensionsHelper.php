<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

class ContentDimensionsHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionsPresetSource;

    /**
     * @return array<string,array<string,mixed>> Dimensions indexed by name with presets indexed by name
     */
    public function contentDimensionsByName(): array
    {
        $dimensions = $this->contentDimensionSource->getContentDimensionsOrderedByPriority();

        $result = [];
        foreach ($dimensions as $dimension) {
            $result[(string)$dimension->identifier] = [
                'label' => $dimension->getConfigurationValue('label'),
                'icon' => $dimension->getConfigurationValue('icon'),

                'default' => $dimension->defaultValue->value,
                'defaultPreset' => $dimension->defaultValue->value,
                'presets' => []
            ];

            foreach ($dimension->values as $value) {
                // TODO: make certain values hidable
                $result[(string)$dimension->identifier]['presets'][$value->value] = [
                    // TODO: name, uriSegment!
                    'values' => [$value->value],
                    'label' => $value->getConfigurationValue('label')
                ];
            }
        }
        return $result;
    }

    /**
     * @param DimensionSpacePoint $dimensions Dimension values indexed by dimension name
     * @return array<string,array<int,string>> Allowed preset names for the given dimension combination indexed by dimension name
     */
    public function allowedPresetsByName(DimensionSpacePoint $dimensions): array
    {
        // TODO: re-implement this here; currently EVERYTHING is allowed!!
        $allowedPresets = [];
        foreach ($dimensions->coordinates as $dimensionName => $dimensionValue) {
            $dimension = $this->contentDimensionSource->getDimension(new ContentDimensionIdentifier($dimensionName));
            if (!is_null($dimension)) {
                $value = $dimension->getValue($dimensionValue);
                if ($value !== null) {
                    $allowedPresets[$dimensionName] = array_keys($dimension->values->values);
                }
            }
        }

        return $allowedPresets;
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
