<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeTypeChange\Dto;

/**
 * The strategy how to handle node type constraint conflicts with already present child nodes
 * when changing a node aggregate's type.
 *
 * - delete will delete all newly disallowed child nodes
 * - markWithTag will hide disallowed child nodes via a SubtreeTag (soft delete), safe for workspace rebase
 *
 * @see NodeAggregateTypeChangeChildConstraintConflictResolutionMarkWithTagStrategy for the mark-with-tag strategy
 *
 * @api DTO of {@see ChangeNodeAggregateType} command
 */
enum NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy: string implements \JsonSerializable
{
    /**
     * This strategy means "we remove all children / grandchildren nodes which do not match the constraint"
     */
    case STRATEGY_DELETE = 'delete';

    /**
     * This strategy means "we only change the NodeAggregateType if all constraints of parents
     * AND children and grandchildren are still respected."
     */
    case STRATEGY_HAPPY_PATH = 'happypath';

    /**
     * This strategy extends happypath by expecting that identically typed children will also be changed, affecting validation.
     * Required e.g. for global type change transformations
     */
    case STRATEGY_PROMISED_CASCADE = 'promisedCascade';

    /**
     * Reconstruct from a serialized string. Acts as the single deserialization entry point for all strategies,
     * including the mark-with-tag strategy which produces a {@see NodeAggregateTypeChangeChildConstraintConflictResolutionMarkWithTagStrategy}.
     *
     * Accepts:
     *   - `"delete"`, `"happypath"`, `"promisedCascade"` — plain strategy strings
     *   - `"mark_with_tag:[tagName]"` — produces a NodeAggregateTypeChangeChildConstraintConflictResolutionMarkWithTagStrategy
     *
     * @throws \InvalidArgumentException for unknown or malformed values
     */
    public static function fromString(string $value): self|NodeAggregateTypeChangeChildConstraintConflictResolutionMarkWithTagStrategy
    {
        if (str_starts_with($value, NodeAggregateTypeChangeChildConstraintConflictResolutionMarkWithTagStrategy::SERIALIZATION_PREFIX)) {
            return NodeAggregateTypeChangeChildConstraintConflictResolutionMarkWithTagStrategy::fromString($value);
        }
        return self::from($value);
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
