<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Domain\TrashBin;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
final readonly class TrashBinPagination implements \JsonSerializable, ProtectedContextAwareInterface
{
    public const DEFAULT_LIMIT = 20;

    public const MAXIMUM_NUMBER_OF_LINKS = 99;

    public function __construct(
        public int $offset,
        public ?int $limit,
    ) {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Trash bin pagination offset cannot be negative', 1750759093);
        }
        if (is_int($limit) && $limit < 1) {
            throw new \InvalidArgumentException('Trash bin pagination limit must be either null or positive', 1750759094);
        }
    }

    public static function default(): self
    {
        return new self(
            offset: 0,
            limit: self::DEFAULT_LIMIT,
        );
    }

    public static function create(int $offset, ?int $limit): self
    {
        return new self($offset, $limit);
    }

    /**
     * @param array<mixed> $array
     */
    public static function fromArray(array $array): self
    {
        $limit = $array['limit'];
        return new self(
            offset: (int)$array['offset'],
            limit: $limit === null ? null : (int)$limit,
        );
    }

    public function withOffset(int $offset): self
    {
        return new self(
            offset: $offset,
            limit: $this->limit,
        );
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }

    public function allowsCallOfMethod($methodName)
    {
        return in_array($methodName, ['withOffset', 'jsonSerialize'], true);
    }
}
