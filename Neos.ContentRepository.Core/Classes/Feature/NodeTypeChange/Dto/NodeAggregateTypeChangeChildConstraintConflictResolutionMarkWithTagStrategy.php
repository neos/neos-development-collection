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

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;

/**
 * Strategy for {@see ChangeNodeAggregateType} that hides newly disallowed child nodes
 * by tagging them with a SubtreeTag ("soft delete"), instead of removing them outright.
 *
 * This is safe for workspace rebase: the node aggregates remain in the graph, so event
 * references to them are still resolvable. Use this strategy instead of
 * {@see NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE}
 * when users have workspace events that reference nodes inside a tethered child that is
 * about to be removed by the type change.
 *
 * Serialization format: `"mark_with_tag:[tagName]"`, e.g. `"mark_with_tag:disabled"`
 *
 * @api DTO of {@see ChangeNodeAggregateType} command
 */
final readonly class NodeAggregateTypeChangeChildConstraintConflictResolutionMarkWithTagStrategy implements \JsonSerializable
{
    /** @internal used by {@see NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::fromString()} */
    public const SERIALIZATION_PREFIX = 'mark_with_tag:';

    public function __construct(public readonly SubtreeTag $subtreeTag)
    {
    }

    /**
     * Reconstruct from a serialized string of the form `"mark_with_tag:[tagName]"`.
     *
     * @throws \InvalidArgumentException for malformed values
     */
    public static function fromString(string $value): self
    {
        if (!str_starts_with($value, self::SERIALIZATION_PREFIX)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid mark-with-tag strategy value "%s". Expected format: "mark_with_tag:[tagName]".', $value),
            );
        }
        $tagName = substr($value, strlen(self::SERIALIZATION_PREFIX));
        if ($tagName === '') {
            throw new \InvalidArgumentException(
                'Invalid mark-with-tag strategy value: tag name must not be empty. Expected format: "mark_with_tag:[tagName]".',
            );
        }
        return new self(SubtreeTag::fromString($tagName));
    }

    public function jsonSerialize(): string
    {
        return self::SERIALIZATION_PREFIX . $this->subtreeTag->value;
    }
}
