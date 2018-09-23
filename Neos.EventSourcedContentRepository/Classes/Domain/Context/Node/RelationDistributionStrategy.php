<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The relation distribution strategy for node aggregates as defined in the NodeType declaration
 * Used for building relations to other node aggregates
 *
 * - `scatter` means that different nodes within the aggregate may be related to different other aggregates (e.g. parent).
 *      Still, specializations pointing to the same node using the fallback mechanism will be kept gathered.
 * - `gatherAll` means that all nodes within the aggregate must be related to the same other aggregate (e.g. parent)
 * - `gatherSpecializations` means that when a node is related to another node aggregate (e.g. parent),
 *      all specializations of that node will be related to that same aggregate while generalizations may be related to others
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

    /**
     * @return RelationDistributionStrategy
     */
    public static function scatter(): RelationDistributionStrategy
    {
        return new static(static::STRATEGY_SCATTER);
    }

    /**
     * @return RelationDistributionStrategy
     */
    public static function gatherAll(): RelationDistributionStrategy
    {
        return new static(static::STRATEGY_GATHER_ALL);
    }

    /**
     * @return RelationDistributionStrategy
     */
    public static function gatherSpecializations(): RelationDistributionStrategy
    {
        return new static(static::STRATEGY_GATHER_SPECIALIZATIONS);
    }

    /**
     * @param null|string $configurationValue
     * @return RelationDistributionStrategy
     * @throws RelationDistributionStrategyIsInvalid
     */
    public static function fromConfigurationValue(?string $configurationValue): RelationDistributionStrategy
    {
        switch ($configurationValue) {
            case self::STRATEGY_SCATTER:
                return static::scatter();
            case self::STRATEGY_GATHER_SPECIALIZATIONS:
                return static::gatherSpecializations();
            case self::STRATEGY_GATHER_ALL:
            case null:
                return static::gatherAll();
            default:
                throw new RelationDistributionStrategyIsInvalid('Given relation distribution strategy ' . $configurationValue . ' is invalid', 1519761485);
        }
    }


    /**
     * @return string
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->strategy;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->strategy;
    }
}
