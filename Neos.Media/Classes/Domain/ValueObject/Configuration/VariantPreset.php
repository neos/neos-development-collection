<?php
declare(strict_types=1);

namespace Neos\Media\Domain\ValueObject\Configuration;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A Value Object for storing configuration of a Variant Preset
 */
final class VariantPreset
{
    /**
     * @var Label
     */
    private $label;

    /**
     * @var AssetClass[]
     */
    private $assetClasses = [];

    /**
     * @var Variant[]
     */
    private $variants = [];

    /**
     * @param Label $label
     */
    public function __construct(Label $label)
    {
        $this->label = $label;
    }

    /**
     * @param array $configuration
     * @return VariantPreset
     */
    public static function fromConfiguration(array $configuration): VariantPreset
    {
        $variantPreset = new static(
            new Label($configuration['label'])
        );
        if (isset($configuration['assetClasses'])) {
            foreach ($configuration['assetClasses'] as $assetClassAsString) {
                $variantPreset->assetClasses[] = new AssetClass($assetClassAsString);
            }
        }
        foreach ($configuration['variants'] as $variantIdentifier => $variantConfiguration) {
            $variantPreset->variants[$variantIdentifier] = Variant::fromConfiguration($variantIdentifier, $variantConfiguration);
        }
        return $variantPreset;
    }

    /**
     * @return Label
     */
    public function label(): Label
    {
        return $this->label;
    }

    /**
     * @return array
     */
    public function assetClasses(): array
    {
        return $this->assetClasses;
    }

    /**
     * @return Variant[]
     */
    public function variants(): array
    {
        return $this->variants;
    }
}
