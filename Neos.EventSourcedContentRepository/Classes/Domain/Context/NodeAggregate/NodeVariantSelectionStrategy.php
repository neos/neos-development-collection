<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeVariantSelectionStrategyIsInvalid;
use Neos\Flow\Annotations as Flow;

/**
 * The node variant selection strategy for node aggregates as selected when creating commands.
 * Used for calculating the affected dimension space points to e.g. build restriction relations to other node aggregates.
 *
 * @Flow\Proxy(false)
 */
final class NodeVariantSelectionStrategy implements \JsonSerializable
{
    /**
     * The "only this" strategy, meaning only the given dimension space point is affected
     */
    const STRATEGY_ONLY_THIS = 'onlyThis';

    /**
     * The "virtual specializations" strategy, meaning only the specializations covered but unoccupied by this node aggregate are affected
     */
    const STRATEGY_VIRTUAL_SPECIALIZATIONS = 'virtualSpecializations';

    /**
     * The "all specializations" strategy, meaning all specializations covered by this node aggregate are affected
     */
    const STRATEGY_ALL_SPECIALIZATIONS = 'allSpecializations';

    /**
     * The "all variants" strategy, meaning all dimension space points covered by this node aggregate are affected
     */
    const STRATEGY_ALL_VARIANTS = 'allVariants';

    /**
     * @var string
     */
    private $strategy;

    private function __construct(string $strategy)
    {
        $this->strategy = $strategy;
    }

    public static function onlyThis(): self
    {
        return new static(static::STRATEGY_ONLY_THIS);
    }

    public static function virtualSpecializations(): self
    {
        return new static(static::STRATEGY_VIRTUAL_SPECIALIZATIONS);
    }

    public static function allSpecializations(): self
    {
        return new static(static::STRATEGY_ALL_SPECIALIZATIONS);
    }

    public static function allVariants(): self
    {
        return new static(static::STRATEGY_ALL_VARIANTS);
    }

    /**
     * @param string $serialization
     * @return self
     * @throws NodeVariantSelectionStrategyIsInvalid
     */
    public static function fromString(string $serialization): self
    {
        if (!$serialization === self::STRATEGY_ONLY_THIS
            && !$serialization === self::STRATEGY_VIRTUAL_SPECIALIZATIONS
            && !$serialization === self::STRATEGY_ALL_SPECIALIZATIONS
        ) {
            throw new NodeVariantSelectionStrategyIsInvalid('Given node variant selection strategy "' . $serialization . '" is invalid, must be one of the defined constants.', 1555074043);
        }

        return new static($serialization);
    }

    public function isOnlyThis(): bool
    {
        return $this->strategy === self::STRATEGY_ONLY_THIS;
    }

    public function isVirtualSpecializations(): bool
    {
        return $this->strategy === self::STRATEGY_VIRTUAL_SPECIALIZATIONS;
    }

    public function isAllSpecializations(): bool
    {
        return $this->strategy === self::STRATEGY_ALL_SPECIALIZATIONS;
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function jsonSerialize(): string
    {
        return $this->strategy;
    }

    public function __toString(): string
    {
        return $this->strategy;
    }
}
