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
 * The materialized sort path of a hierarchy relation: one fractional index key per tree level,
 * joined with {@see self::SEPARATOR}, e.g. `a0/a0/b3/ZzV`.
 *
 * Ordering the hierarchy relations of a subgraph by this value yields depth-first document order,
 * and the descendants of a node are a contiguous range.
 *
 * A path has one segment per level below the root edge: `a0` is a root node (depth 1, no separator),
 * `a0/a0` is its first child.
 *
 * @internal
 */
final readonly class NodeSortPath
{
    /**
     *  Fractional index keys are base 62 {@see \Neos\ContentGraph\DoctrineDbalAdapter\FractionalIndexing},
     *  so every character is in `0-9A-Za-z` (0x30-0x7A). The separator 0x2F sorts *below* all of them,
     *  which is exactly what makes prefix ordering correct:
     *
     *      a0     <  a0/x      a parent sorts before its own children
     *      a0/x   <  a0V       a child sorts before its parent's succeeding sibling ('/' 0x2F < 'V' 0x56)
     */
    public const string SEPARATOR = '/';

    /**
     * Above this, the sibling set is rebalanced instead of the key being used as-is.
     *
     * Fractional keys grow by roughly 0.2 characters per insert into the *same* gap, so this is reached
     * after ~170 such inserts. Pure appends and pure prepends never approach it: both stay at ~5
     * characters even after 10^6 operations.
     */
    public const int MAX_KEY_LENGTH = 36;

    /**
     * Maximum length of a path to fit into the index.
     *
     * A sort path index of 3000 bytes allows storing up to 3000 characters. A well-balanced tree will have 3-5 chars
     * per path segment on average. This would allow 500-750 level deep trees.
     *
     * | Avg. path segment length  | Max. levels |
     * |---------------------------|-------------|
     * | 2 chars + /               | 1000        |
     * | 3 chars + /               | 750         |
     * | 4 chars + /               | 600         |
     * | 5 chars + /               | 500         |
     * | 10 chars + /              | 272         |
     * | 15 chars + /              | 187         |
     * | 36 chars + / (worst case) | 81          |
     *
     */
    public const int MAX_LENGTH = 3000;

    private function __construct(
        public string $value,
    ) {
        $this->validateMaxLength($value);
    }

    public static function fromString(string $value): self
    {
        if ($value === '') {
            throw new \InvalidArgumentException('NodeSortPath must not be empty', 1790261368);
        }

        return new self($value);
    }

    /**
     * Replaces the nodeSortKey (last element in the path) with a new one.
     */
    public function withReplacedNodeSortKey(string $nodeSortKey): self
    {
        $this->validateNodeSortKey($nodeSortKey);

        if ($this->isRoot()) {
            return new self($nodeSortKey);
        }

        return $this->getParent()->withAddedNodeSortKeySegment($nodeSortKey);
    }

    /**
     * Adds a new segment to the path.
     */
    public function withAddedNodeSortKeySegment(string $nodeSortKey): self
    {
        $this->validateNodeSortKey($nodeSortKey);

        return new self($this->value . self::SEPARATOR . $nodeSortKey);
    }

    public function isRoot(): bool
    {
        return strrpos($this->value, self::SEPARATOR) === false;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function getParent(): self
    {
        if ($this->isRoot()) {
            throw new \InvalidArgumentException('Cannot get parent of root NodeSortPath', 1790261364);
        }

        /** @var int $separatorPosition */
        $separatorPosition = strrpos($this->value, self::SEPARATOR);
        return new self(substr($this->value, 0, $separatorPosition));
    }

    /**
     * Whether this node's own key has grown past {@see MAX_KEY_LENGTH}.
     */
    public function nodeSortKeyExceedsMaxKeyLength(): bool
    {
        return strlen($this->getNodeSortKey()) > self::MAX_KEY_LENGTH;
    }

    /**
     * This node's own fractional index key, i.e. the last segment of the path.
     */
    public function getNodeSortKey(): string
    {
        if ($this->isRoot()) {
            return $this->value;
        }
        /** @var int $separatorPosition */
        $separatorPosition = strrpos($this->value, self::SEPARATOR);
        return substr($this->value, $separatorPosition + 1);
    }

    /**
     * Allows to search subtrees of the given NodeSortPath.
     * E.g: a0 -> rangeStart = a0/
     *         -> rangeEnd   = a00 ("0" (0x30) is "/" (0x2F) + 1 in ASCII values)
     *
     * This allows `NodeSortPath >= rangeStart AND NodeSortPath < rangeEnd` to find all descendent paths.
     */
    public function rangeStart(): string
    {
        return $this->value . self::SEPARATOR;
    }

    /**
     * @see self::rangeStart()
     */
    public function rangeEnd(): string
    {
        return $this->value . chr(ord(self::SEPARATOR) + 1);
    }

    private function validateMaxLength(string $value): void
    {
        if (strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException('Length of NodeSortPath exceeds max length: ' . strlen($value));
        }
    }

    /**
     * @param string $nodeSortKey
     * @return void
     */
    private function validateNodeSortKey(string $nodeSortKey): void
    {
        if ($nodeSortKey === '') {
            throw new \InvalidArgumentException('NodeSortKey must not be empty', 1790261874);
        }
    }
}
