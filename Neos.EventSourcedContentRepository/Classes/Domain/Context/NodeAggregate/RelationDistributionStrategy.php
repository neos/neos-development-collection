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
 * The relation distribution strategy for node aggregates as defined in the NodeType declaration
 * Used for building relations to other node aggregates
 *
 * - `scatter` means that different nodes within the aggregate may be related to different other aggregates (e.g. parent).
 *      Still, specializations pointing to the same node using the fallback mechanism will be kept gathered.
 * - `gatherAll` means that all nodes within the aggregate must be related to the same other aggregate (e.g. parent)
 * - `gatherSpecializations` means that when a node is related to another node aggregate (e.g. parent),
 *      all specializations of that node will be related to that same aggregate while generalizations may be related to others
 *
 * @Flow\Proxy(false)
 */
final class RelationDistributionStrategy implements \JsonSerializable
{
    const STRATEGY_SCATTER = 'scatter';
    const STRATEGY_GATHER_ALL = 'gatherAll';
    const STRATEGY_GATHER_SPECIALIZATIONS = 'gatherSpecializations';

    /**
     * @var string
     */
    protected $strategy;

    protected function __construct(string $strategy)
    {
        $this->strategy = $strategy;
    }

    public static function scatter(): self
    {
        return new static(static::STRATEGY_SCATTER);
    }

    public static function gatherAll(): self
    {
        return new static(static::STRATEGY_GATHER_ALL);
    }

    public static function gatherSpecializations(): self
    {
        return new static(static::STRATEGY_GATHER_SPECIALIZATIONS);
    }

    /**
     * @param null|string $serialization
     * @return RelationDistributionStrategy
     * @throws RelationDistributionStrategyIsInvalid
     */
    public static function fromString(?string $serialization): self
    {
        switch ($serialization) {
            case self::STRATEGY_SCATTER:
                return static::scatter();
            case self::STRATEGY_GATHER_SPECIALIZATIONS:
                return static::gatherSpecializations();
            case self::STRATEGY_GATHER_ALL:
            case null:
                return static::gatherAll();
            default:
                throw new RelationDistributionStrategyIsInvalid('Given relation distribution strategy ' . $serialization . ' is invalid', 1519761485);
        }
    }

    public function isScatter(): bool
    {
        return $this->strategy === self::STRATEGY_SCATTER;
    }

    public function isGatherAll(): bool
    {
        return $this->strategy === self::STRATEGY_GATHER_ALL;
    }

    public function isGatherSpecializations(): bool
    {
        return $this->strategy === self::STRATEGY_GATHER_SPECIALIZATIONS;
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
