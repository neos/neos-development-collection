<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * The materialised sort path of a hierarchy relation: one fractional index key per tree level,
 * joined with {@see self::SEPARATOR}, e.g. `a0/a0/b3/ZzV`.
 *
 * Ordering the hierarchy relations of a subgraph by this value yields depth-first document order,
 * and the descendants of a node are a contiguous range {@see rangeStart()} / {@see rangeEnd()}.
 *
 * WHY THE SEPARATOR IS "/"
 * ========================
 *
 * Fractional index keys are base 62 {@see \Neos\ContentGraph\DoctrineDbalAdapter\FractionalIndexing},
 * so every character is in `0-9A-Za-z` (0x30-0x7A). The separator 0x2F sorts *below* all of them,
 * which is exactly what makes prefix ordering correct:
 *
 *     a0     <  a0/x      a parent sorts before its own children
 *     a0/x   <  a0V       a child sorts before its parent's succeeding sibling ('/' 0x2F < 'V' 0x56)
 *
 * This holds only under byte-wise comparison, which is why the column is VARBINARY and not VARCHAR.
 * The server default collations (utf8mb4_0900_ai_ci on MySQL 8, utf8mb4_uca1400_ai_ci on MariaDB 11.4+)
 * are case-insensitive and would make 'Z' equal 'z', silently destroying the ordering.
 *
 * @internal
 */
final readonly class NodeSortPath
{
    public const SEPARATOR = '/';

    /**
     * Must match the `sortpath` column length in {@see \Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphSchemaBuilder}.
     *
     * Exceeding this is a structural error - at normal segment sizes it means a tree roughly 150 levels
     * deep - and cannot be fixed by rebalancing, so it throws rather than triggering one.
     */
    public const MAX_LENGTH = 768;

    /**
     * Above this, the sibling set is rebalanced instead of the key being used as-is.
     *
     * Fractional keys grow by roughly 0.17 characters per insert into the *same* gap, so this is reached
     * after ~170-190 such inserts. Pure appends and pure prepends never approach it: both stay at ~5
     * characters even after 10^6 operations.
     */
    public const MAX_KEY_LENGTH = 12;

    private function __construct(
        public string $value,
    ) {
    }

    /**
     * The empty path above the root nodes. Root hierarchy relations use {@see NodeRelationAnchorPoint::forRootEdge()}
     * as their parent anchor and therefore have no parent relation to inherit a prefix from; their own paths are
     * bare keys without a leading separator.
     */
    public static function root(): self
    {
        return new self('');
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Whether this node's own key has grown past {@see MAX_KEY_LENGTH} and its sibling set should be rebalanced.
     */
    public function exceedsMaxKeyLength(): bool
    {
        return !$this->isRoot() && strlen($this->localKey()) > self::MAX_KEY_LENGTH;
    }

    /**
     * Guards the `sortpath` column width. Call this once the value is final - that is, after any rebalance -
     * because a rebalance is what makes an over-long key short again.
     *
     * This must be enforced in PHP rather than left to the database: without STRICT_TRANS_TABLES, MariaDB
     * truncates an over-long value instead of erroring, which would silently corrupt the ordering.
     */
    public function assertFitsColumn(): void
    {
        if (strlen($this->value) > self::MAX_LENGTH) {
            throw new \RuntimeException(sprintf(
                'Node sort path exceeds the maximum length of %d bytes (got %d): %s. '
                . 'At normal segment sizes this means a tree roughly 150 levels deep; rebalancing cannot shorten it.',
                self::MAX_LENGTH,
                strlen($this->value),
                $this->value
            ), 1775980001);
        }
    }

    public function isRoot(): bool
    {
        return $this->value === '';
    }

    /**
     * This node's own fractional index key, i.e. the last segment of the path.
     */
    public function localKey(): string
    {
        $this->assertNotRoot(__FUNCTION__);
        $separatorPosition = strrpos($this->value, self::SEPARATOR);
        return $separatorPosition === false ? $this->value : substr($this->value, $separatorPosition + 1);
    }

    public function parent(): self
    {
        $this->assertNotRoot(__FUNCTION__);
        $separatorPosition = strrpos($this->value, self::SEPARATOR);
        return $separatorPosition === false ? self::root() : new self(substr($this->value, 0, $separatorPosition));
    }

    /**
     * All proper prefixes of this path, closest ancestor first, excluding this path and the empty root.
     *
     * For `a0/b3/ZzV` this is `['a0/b3', 'a0']` - the paths of every ancestor, which lets ancestor lookups
     * be a plain `sortpath IN (...)` instead of a recursive CTE.
     *
     * @return array<int,string>
     */
    public function ancestorPaths(): array
    {
        $paths = [];
        $current = $this;
        while (!$current->isRoot()) {
            $current = $current->parent();
            if (!$current->isRoot()) {
                $paths[] = $current->value;
            }
        }
        return $paths;
    }

    public function child(string $key): self
    {
        if ($key === '') {
            throw new \RuntimeException('Cannot append an empty fractional index key to a node sort path', 1775980002);
        }
        return new self($this->isRoot() ? $key : $this->value . self::SEPARATOR . $key);
    }

    /**
     * Inclusive lower bound of this node's descendants.
     */
    public function rangeStart(): string
    {
        $this->assertNotRoot(__FUNCTION__);
        return $this->value . self::SEPARATOR;
    }

    /**
     * Exclusive upper bound of this node's descendants.
     *
     * The bound is exact. For `a0/b3` the range is ['a0/b3/', 'a0/b30'): a sibling `a0/b3V` falls above it
     * because 'V' (0x56) > '0' (0x30), and a sibling keyed literally `a0/b30` - which is legal, since the
     * head 'b' denotes a three character integer part - is excluded by the strict upper bound.
     */
    public function rangeEnd(): string
    {
        $this->assertNotRoot(__FUNCTION__);
        return $this->value . chr(ord(self::SEPARATOR) + 1);
    }

    public function depth(): int
    {
        return $this->isRoot() ? 0 : substr_count($this->value, self::SEPARATOR) + 1;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function assertNotRoot(string $method): void
    {
        if ($this->isRoot()) {
            throw new \RuntimeException(sprintf('%s() must not be called on the empty root sort path', $method), 1775980003);
        }
    }
}
