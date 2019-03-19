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
     * @var MediaTypePattern[]
     */
    private $mediaTypePatterns = [];

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
        if (!isset($configuration['mediaTypePatterns'])) {
            throw new \InvalidArgumentException(sprintf('Missing mediaTypePatterns definition in configuration for variant preset %s.', $configuration['label']), 1552995185);
        }
        foreach ($configuration['mediaTypePatterns'] as $mediaTypeAsString) {
            $variantPreset->mediaTypePatterns[] = new MediaTypePattern($mediaTypeAsString);
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
     * Checks if any of the defined media type patterns matches the given concrete media type.
     *
     * @param string $mediaType
     * @return bool
     */
    public function matchesMediaType(string $mediaType): bool
    {
        foreach ($this->mediaTypePatterns as $mediaTypePattern) {
            if ($mediaTypePattern->matches($mediaType)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return MediaTypePattern[]
     */
    public function mediaTypePatterns(): array
    {
        return $this->mediaTypePatterns;
    }

    /**
     * @return Variant[]
     */
    public function variants(): array
    {
        return $this->variants;
    }
}
