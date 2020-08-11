<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Service;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\ValueObject\Configuration;
use Neos\Media\Domain\ValueObject\Configuration\VariantPreset;
use Neos\Media\Exception\AssetVariantGeneratorException;
use Neos\Media\Exception\ImageFileException;
use Neos\Utility\ObjectAccess;

/**
 * @Flow\Scope("singleton")
 */
class AssetVariantGenerator
{
    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\InjectConfiguration(path="variantPresets", package="Neos.Media")
     * @var array
     */
    protected $variantPresetsConfiguration = [];

    /**
     * @var VariantPreset[]
     */
    protected $variantPresets = [];

    /**
     * @return VariantPreset[]
     */
    public function getVariantPresets(): array
    {
        if ($this->variantPresets === [] && $this->variantPresetsConfiguration !== []) {
            foreach ($this->variantPresetsConfiguration as $identifier => $configuration) {
                $this->variantPresets[$identifier] = VariantPreset::fromConfiguration($configuration);
            }
        }
        return $this->variantPresets;
    }

    /**
     * @param AssetInterface $asset
     * @return ImageVariant[] The created variants (if any), with the preset identifier as array key
     * @throws AssetVariantGeneratorException
     * @throws Exception
     * @throws ImageFileException
     * @throws InvalidConfigurationException
     */
    public function createVariants(AssetInterface $asset): array
    {
        // Currently only Image Variants are supported. Other asset classes can be supported, as soon as there is a common
        // interface for creating and adding variants.
        //
        if (!$asset instanceof Image) {
            return [];
        }

        $createdVariants = [];
        foreach ($this->getVariantPresets() as $presetIdentifier => $preset) {
            if ($preset->matchesMediaType($asset->getMediaType())) {
                foreach ($preset->variants() as $variantIdentifier => $variantConfiguration) {
                    $createdVariants[$presetIdentifier . ':' . $variantIdentifier] = $this->createVariant($asset, $presetIdentifier, $variantConfiguration);
                    $asset->addVariant($createdVariants[$presetIdentifier . ':' . $variantIdentifier]);
                }
            }
        }

        return $createdVariants;
    }

    /**
     * @param Image $originalAsset
     * @param string $presetIdentifier
     * @param Configuration\Variant $variantConfiguration
     * @return ImageVariant
     * @throws AssetVariantGeneratorException
     * @throws Exception
     * @throws ImageFileException
     * @throws InvalidConfigurationException
     */
    protected function createVariant(Image $originalAsset, string $presetIdentifier, Configuration\Variant $variantConfiguration): AssetVariantInterface
    {
        $adjustments = [];
        foreach ($variantConfiguration->adjustments() as $adjustmentConfiguration) {
            assert($adjustmentConfiguration instanceof Configuration\Adjustment);
            $adjustmentClassName = $adjustmentConfiguration->type();
            if (!class_exists($adjustmentClassName)) {
                throw new AssetVariantGeneratorException(sprintf('Unknown image variant adjustment type "%s".', $adjustmentClassName), 1548066841);
            }
            $adjustment = new $adjustmentClassName();
            if (!$adjustment instanceof ImageAdjustmentInterface) {
                throw new AssetVariantGeneratorException(sprintf('Image variant adjustment "%s" does not implement "%s".', $adjustmentClassName, ImageAdjustmentInterface::class), 1548071529);
            }
            foreach ($adjustmentConfiguration->options() as $key => $value) {
                ObjectAccess::setProperty($adjustment, $key, $value);
            }
            $adjustments[] = $adjustment;
        }

        $imageVariant = $this->createImageVariant($originalAsset);
        $imageVariant->setPresetIdentifier($presetIdentifier);
        $imageVariant->setPresetVariantName($variantConfiguration->identifier());

        foreach ($adjustments as $adjustment) {
            $imageVariant->addAdjustment($adjustment);
        }

        return $imageVariant;
    }

    /**
     * @param Image $imageAsset
     * @return ImageVariant
     */
    protected function createImageVariant(Image $imageAsset): ImageVariant
    {
        return new ImageVariant($imageAsset);
    }
}
