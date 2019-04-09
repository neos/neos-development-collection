<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\Utility;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Annotations as Flow;

/**
 * The NodeAggregateIdentifier supersedes the Node Identifier from Neos <= 4.x.
 *
 * @Flow\Proxy(false)
 */
final class NodeAggregateIdentifier implements \JsonSerializable, CacheAwareInterface
{
    /**
     * A preg pattern to match against node aggregate identifiers
     */
    const PATTERN = '/^([a-z0-9\-]{1,255})$/';

    /**
     * @var string
     */
    private $value;

    private function __construct(string $value)
    {
        if (!preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException('Invalid node aggregate identifier "' . $value . '" (a node aggregate identifier must only contain lowercase characters, numbers and the "-" sign).', 1505840197862);
        }
        $this->value = $value;
    }

    public static function create(): self
    {
        return new static(Algorithms::generateUUID());
    }

    public static function fromString(string $value): self
    {
        return new static($value);
    }

    /**
     * @param NodeName $childNodeName
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return static
     * @throws \Exception
     */
    public static function forAutoCreatedChildNode(NodeName $childNodeName, NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        return new static(Utility::buildAutoCreatedChildNodeIdentifier((string)$childNodeName, (string)$nodeAggregateIdentifier));
    }

    /**
     * @param NodeAggregateIdentifier $other
     * @return bool
     */
    public function equals(NodeAggregateIdentifier $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        return $this->value;
    }
}
