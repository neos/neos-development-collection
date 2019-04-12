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

use Neos\Flow\Annotations as Flow;

/**
 * The disabling strategy for node aggregates as selected when creating commands.
 * Used for building restriction relations to other node aggregates
 *
 * - `scatter` means that different nodes within the aggregate may be individually restricted.
 * - `gatherVirtualSpecializations` means that all virtual specializations of the node will be restricted alongside with it
 * - `gatherAllSpecializations` means that all specializations of the node will be restricted alongside with it,
 *      regardless of whether they are virtual or another node of the aggregate originates there.
 *
 * @Flow\Proxy(false)
 */
final class NodeDisablingStrategy implements \JsonSerializable
{
    const STRATEGY_SCATTER = 'scatter';
    const STRATEGY_GATHER_VIRTUAL_SPECIALIZATIONS = 'gatherVirtualSpecializations';
    const STRATEGY_GATHER_ALL_SPECIALIZATIONS = 'gatherAllSpecializations';

    /**
     * @var string
     */
    private $strategy;

    private function __construct(string $strategy)
    {
        $this->strategy = $strategy;
    }

    public static function scatter(): self
    {
        return new static(static::STRATEGY_SCATTER);
    }

    public static function gatherVirtualSpecializations(): self
    {
        return new static(static::STRATEGY_GATHER_VIRTUAL_SPECIALIZATIONS);
    }

    public static function gatherAllSpecializations(): self
    {
        return new static(static::STRATEGY_GATHER_ALL_SPECIALIZATIONS);
    }

    /**
     * @param null|string $serialization
     * @return self
     * @throws NodeDisablingStrategyIsInvalid
     */
    public static function fromString(string $serialization): self
    {
        if (!$serialization === self::STRATEGY_SCATTER
            && !$serialization === self::STRATEGY_GATHER_VIRTUAL_SPECIALIZATIONS
            && !$serialization === self::STRATEGY_GATHER_ALL_SPECIALIZATIONS
        ) {
            throw new NodeDisablingStrategyIsInvalid('Given node disabling strategy "' . $serialization . '" is invalid, must be one of the defined constants.', 1555074043);
        }

        return new static($serialization);
    }

    public function isScatter(): bool
    {
        return $this->strategy === self::STRATEGY_SCATTER;
    }

    public function isGatherVirtualSpecializations(): bool
    {
        return $this->strategy === self::STRATEGY_GATHER_VIRTUAL_SPECIALIZATIONS;
    }

    public function isGatherAllSpecializations(): bool
    {
        return $this->strategy === self::STRATEGY_GATHER_ALL_SPECIALIZATIONS;
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
