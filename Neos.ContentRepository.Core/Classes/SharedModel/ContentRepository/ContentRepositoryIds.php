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

namespace Neos\ContentRepository\Core\SharedModel\ContentRepository;

/**
 * @implements \IteratorAggregate<ContentRepositoryId>
 * @api
 */
final readonly class ContentRepositoryIds implements \IteratorAggregate, \Countable
{
    /**
     * @var array<ContentRepositoryId>
     */
    private array $ids;

    private function __construct(ContentRepositoryId ...$ids)
    {
        $this->ids = $ids;
    }

    /**
     * @param array<ContentRepositoryId|string> $ids
     */
    public static function fromArray(array $ids): self
    {
        $processedIds = [];
        foreach ($ids as $id) {
            if (is_string($id)) {
                $id = ContentRepositoryId::fromString($id);
            }
            if (!$id instanceof ContentRepositoryId) {
                throw new \InvalidArgumentException(sprintf('Expected string or instance of %s, got: %s', ContentRepositoryId::class, get_debug_type($id)), 1720424666);
            }
            $processedIds[] = $id;
        }
        return new self(...$processedIds);
    }

    public function getIterator(): \Traversable
    {
        return yield from $this->ids;
    }

    public function count(): int
    {
        return count($this->ids);
    }
}
