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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;


/**
 * @api
 * @implements \IteratorAggregate<ContentStreamId>
 */
final class ContentStreamIds implements \IteratorAggregate
{
    /**
     * @param ContentStreamId[] $contentStreamIds
     */
    private function __construct(
        private readonly array $contentStreamIds,
    ) {
        if ($this->contentStreamIds === []) {
            throw new \InvalidArgumentException('ContentStreamIds must not be empty', 1681306355);
        }
    }

    public static function fromContentStreamIds(ContentStreamId ...$contentStreamIds): self
    {
        return new self($contentStreamIds);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->contentStreamIds);
    }

    public function contain(ContentStreamId $contentStreamId): bool
    {
        foreach ($this->contentStreamIds as $id) {
            if ($id->equals($contentStreamId)) {
                return true;
            }
        }
        return false;
    }
}
