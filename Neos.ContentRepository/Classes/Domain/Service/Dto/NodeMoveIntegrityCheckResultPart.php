<?php

namespace Neos\ContentRepository\Domain\Service\Dto;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Flow\Annotations as Flow;

/**
 * {@see NodeMoveIntegrityCheckService} for detailed explanation of usage
 *
 * @Flow\Proxy(false)
 */
final class NodeMoveIntegrityCheckResultPart
{
    /**
     * the dimensions values array, like it is stored in {@see Context::getDimensions()}:
     * [
     *    "language" => ["de", "en"],        <-- including fallbacks
     *    "country" => ["ger"]               <-- with all dimensions defined
     * ]
     * @var array
     */
    private $dimensions;

    /**
     * a human-readable label, like "Language: Deutsch, Country: Germany"
     *
     * @var string
     */
    private $dimensionsLabel;

    /**
     * @var bool
     */
    private $hasViolation;

    /**
     * @param array $dimensions
     * @param string $dimensionsLabel
     * @param bool $hasViolation
     */
    private function __construct(array $dimensions, string $dimensionsLabel, bool $hasViolation)
    {
        $this->dimensions = $dimensions;
        $this->dimensionsLabel = $dimensionsLabel;
        $this->hasViolation = $hasViolation;
    }

    public static function noViolation(Context $context, ContentDimensionPresetSourceInterface $contentDimensionPresetSource): self
    {
        return new self($context->getDimensions(), self::generateDimensionsLabel($context->getDimensions(), $contentDimensionPresetSource), false);
    }

    public static function violationNoParentInDimension(Context $context, ContentDimensionPresetSourceInterface $contentDimensionPresetSource): self
    {
        return new self($context->getDimensions(), self::generateDimensionsLabel($context->getDimensions(), $contentDimensionPresetSource), true);
    }

    private static function generateDimensionsLabel(array $dimensions, ContentDimensionPresetSourceInterface $contentDimensionPresetSource): string
    {
        $dimensionStringParts = [];
        foreach ($dimensions as $dimensionName => $dimensionValues) {
            $preset = $contentDimensionPresetSource->findPresetByDimensionValues($dimensionName, $dimensionValues);
            $dimensionStringPart = '';
            // fallback to the technical dimension name if no label found
            $dimensionStringPart .= $contentDimensionPresetSource->getAllPresets()[$dimensionName]['label'] ?? $dimensionName;
            $dimensionStringPart .= ' ';
            // fallback to the technical dimension values if no label found
            $dimensionStringPart .= $preset['label'] ?? json_encode($dimensionValues);
            $dimensionStringParts[] = $dimensionStringPart;
        }
        return implode(', ', $dimensionStringParts);
    }

    /**
     * @return string
     */
    public function getDimensionsLabel(): string
    {
        return $this->dimensionsLabel;
    }

    /**
     * @return bool
     */
    public function isViolated(): bool
    {
        return $this->hasViolation;
    }
}
