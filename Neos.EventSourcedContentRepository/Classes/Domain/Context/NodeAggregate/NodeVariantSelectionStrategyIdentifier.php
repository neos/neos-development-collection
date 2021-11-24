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
 * Used for calculating the affected dimension space points to e.g. build restriction relations to other node aggregates;
 * or to remove nodes.
 *
 * @Flow\Proxy(false)
 */
final class NodeVariantSelectionStrategyIdentifier implements \JsonSerializable
{
    /**
     * The "only given" strategy, meaning only the given dimension space point is affected
     */
    const STRATEGY_ONLY_GIVEN_VARIANT = 'onlyGivenVariant';

    /**
     * The "virtual specializations" strategy, meaning only the specializations covered but unoccupied by this node aggregate are affected.
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
    private $identifier;

    private function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public static function onlyGivenVariant(): self
    {
        return new static(static::STRATEGY_ONLY_GIVEN_VARIANT);
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
        if ($serialization !== self::STRATEGY_ONLY_GIVEN_VARIANT
            && $serialization !== self::STRATEGY_VIRTUAL_SPECIALIZATIONS
            && $serialization !== self::STRATEGY_ALL_SPECIALIZATIONS
            && $serialization !== self::STRATEGY_ALL_VARIANTS
        ) {
            throw new NodeVariantSelectionStrategyIsInvalid('Given node variant selection strategy "' . $serialization . '" is invalid, must be one of the defined constants.', 1555074043);
        }

        return new static($serialization);
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function jsonSerialize(): string
    {
        return $this->identifier;
    }

    public function __toString(): string
    {
        return $this->identifier;
    }

    public function equals(NodeVariantSelectionStrategyIdentifier $other): bool
    {
        return $this->identifier === $other->identifier;
    }
}
