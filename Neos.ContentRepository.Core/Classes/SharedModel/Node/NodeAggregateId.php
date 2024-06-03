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

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;

/**
 * The NodeAggregateId supersedes the Node Identifier from Neos <= 9.x.
 *
 * @api
 */
final readonly class NodeAggregateId implements \JsonSerializable
{
    /**
     * A preg pattern to match against node aggregate identifiers
     */
    private const PATTERN = '/^([a-z0-9\-]{1,64})$/';

    private function __construct(
        public string $value
    ) {
    }

    public static function create(): self
    {
        return new self(UuidFactory::create());
    }

    public static function fromString(string $value): self
    {
        if (!self::hasValidFormat($value)) {
            throw new \InvalidArgumentException(
                'Invalid node aggregate identifier "' . $value
                . '" (a node aggregate identifier must only contain lowercase characters, numbers and the "-" sign).',
                1505840197862
            );
        }
        return new self($value);
    }

    public static function tryFromString(string $value): ?self
    {
        return self::hasValidFormat($value) ? new self($value) : null;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    private static function hasValidFormat(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }
}
