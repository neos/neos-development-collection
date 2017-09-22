<?php

namespace Neos\ContentRepository\Domain\Context\Dimension\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\Dimension\Exception;

/**
 * The content dimension value domain model
 */
class ContentDimensionValue
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var ContentDimensionValue
     */
    protected $generalization;

    /**
     * @var array
     */
    protected $specializations = [];

    /**
     * @var int
     */
    protected $depth = 0;

    /**
     * @param string $value
     * @param ContentDimensionValue|null $generalization
     */
    public function __construct(string $value, ?ContentDimensionValue $generalization = null)
    {
        $this->value = $value;
        if ($generalization) {
            $this->generalization = $generalization;
            $this->depth = $generalization->getDepth() + 1;
        }
    }

    /**
     * @param ContentDimensionValue $specialization
     */
    public function registerSpecialization(ContentDimensionValue $specialization): void
    {
        $this->specializations[$specialization->getValue()] = $specialization;
    }

    /**
     * @return array|ContentDimensionValue[]
     */
    public function getSpecializations(): array
    {
        return $this->specializations;
    }

    /**
     * @return ContentDimensionValue|null
     */
    public function getGeneralization(): ?ContentDimensionValue
    {
        return $this->generalization;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @param ContentDimensionValue $fallback
     * @return int
     * @throws Exception\InvalidFallbackException
     */
    public function calculateFallbackDepth(ContentDimensionValue $fallback): int
    {
        $fallbackDepth = 0;
        $fallbackFound = false;
        $currentGeneralization = $this;
        while ($currentGeneralization && !$fallbackFound) {
            if ($currentGeneralization === $fallback) {
                $fallbackFound = true;
            } else {
                $currentGeneralization = $currentGeneralization->getGeneralization();
                $fallbackDepth++;
            }
        }
        if (!$fallbackFound) {
            throw new Exception\InvalidFallbackException();
        }

        return $fallbackDepth;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
