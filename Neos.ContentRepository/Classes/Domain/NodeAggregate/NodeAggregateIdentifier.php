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

namespace Neos\ContentRepository\Domain\NodeAggregate;

use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\Utility;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Annotations as Flow;

/**
 * The NodeAggregateIdentifier supersedes the Node Identifier from Neos <= 4.x.
 */
#[Flow\Proxy(false)]
final class NodeAggregateIdentifier implements \JsonSerializable, CacheAwareInterface, \Stringable
{
    /**
     * A preg pattern to match against node aggregate identifiers
     */
    const PATTERN = '/^([a-z0-9\-]{1,255})$/';

    private function __construct(
        private string $value
    ) {
        if (!preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException('Invalid node aggregate identifier "' . $value . '" (a node aggregate identifier must only contain lowercase characters, numbers and the "-" sign).', 1505840197862);
        }
    }

    public static function create(): self
    {
        return new self(Algorithms::generateUUID());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * @throws \Exception
     */
    public static function forAutoCreatedChildNode(NodeName $childNodeName, NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        return new self(Utility::buildAutoCreatedChildNodeIdentifier((string)$childNodeName, (string)$nodeAggregateIdentifier));
    }

    public function equals(NodeAggregateIdentifier $other): bool
    {
        return $this->value === (string)$other;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->value;
    }
}
