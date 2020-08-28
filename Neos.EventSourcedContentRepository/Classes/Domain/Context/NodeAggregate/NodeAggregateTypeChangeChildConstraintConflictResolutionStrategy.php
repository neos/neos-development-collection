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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategyIsUnknown;

/**
 * The strategy how to handle node type constraint conflicts with already present child nodes
 * when changing a node aggregate's type.
 *
 * - delete will delete all newly disallowed child nodes
 * @Flow\Proxy(false)
 */
final class NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy implements \JsonSerializable
{
    /**
     * This strategy means "we remove all children / grandchildren nodes which do not match the constraint"
     */
    const STRATEGY_DELETE = 'delete';

    /**
     * This strategy means "we only change the NodeAggregateType if all constraints of parents AND children and grandchildren are still respected."
     */
    const STRATEGY_HAPPYPATH = 'happypath';

    /**
     * @var string
     */
    private $strategy;

    private function __construct(string $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * @param string $strategy
     * @return NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
     * @throws NodeAggregateTypeChangeChildConstraintConflictResolutionStrategyIsUnknown
     */
    public static function fromString(string $strategy): self
    {
        if (!in_array($strategy, [self::STRATEGY_DELETE, self::STRATEGY_HAPPYPATH])) {
            throw new NodeAggregateTypeChangeChildConstraintConflictResolutionStrategyIsUnknown(
                'Given strategy "' . $strategy . '" is not known for resolving child node type constraint conflicts when changing a node type.',
                15200134492
            );
        }
        return new self($strategy);
    }

    public static function delete(): NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
    {
        return new self(self::STRATEGY_DELETE);
    }

    public static function happypath(): NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
    {
        return new self(self::STRATEGY_HAPPYPATH);
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
    public function __toString(): string
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
}
