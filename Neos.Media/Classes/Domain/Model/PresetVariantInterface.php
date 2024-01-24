<?php
namespace Neos\Media\Domain\Model;

/**
 * Methods to identify a variant to be based on a preset
 */
interface PresetVariantInterface
{
    /**
     * Sets the identifier of the variant preset which created this variant (if any)
     *
     * @param string $presetIdentifier For example: 'Acme.Demo:Preset1'
     */
    public function setPresetIdentifier(string $presetIdentifier): void;

    /**
     * Returns the identifier of the variant preset which created this variant (if any)
     *
     * @return string|null
     */
    public function getPresetIdentifier(): ?string;

    /**
     * @param string $presetVariantName
     */
    public function setPresetVariantName(string $presetVariantName): void;

    /**
     * @return string|null
     */
    public function getPresetVariantName(): ?string;
}
