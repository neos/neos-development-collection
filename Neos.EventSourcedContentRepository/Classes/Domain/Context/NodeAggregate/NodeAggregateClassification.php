<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The classification of a node aggregate
 *
 * @Flow\Proxy(false)
 */
final class NodeAggregateClassification implements \JsonSerializable
{
    /**
     * Denotes a regular node aggregate
     */
    const CLASSIFICATION_REGULAR = 'regular';

    /**
     * Denotes a root node aggregate which
     * * does not have parents
     * * always originates in the empty dimension space point
     * * cannot be varied
     */
    const CLASSIFICATION_ROOT = 'root';

    /**
     * Denotes a tethered node aggregate which
     * * is created and removed alongside a regular parent
     * * cannot be directly structurally changed
     */
    const CLASSIFICATION_TETHERED = 'tethered';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        if ($value !== self::CLASSIFICATION_REGULAR && $value !== self::CLASSIFICATION_ROOT && $value !== self::CLASSIFICATION_TETHERED) {
            throw new \DomainException('Invalid node aggregate classification "' . $value . '", must be one of the defined constants.', 1554556942);
        }

        return new self($value);
    }

    public static function fromNode(NodeInterface $node): self
    {
        if ($node->isRoot()) {
            return self::root();
        }

        if ($node->isTethered()) {
            return self::tethered();
        }

        return self::regular();
    }

    public static function root(): self
    {
        return new self(self::CLASSIFICATION_ROOT);
    }

    public static function regular(): self
    {
        return new self(self::CLASSIFICATION_REGULAR);
    }

    public static function tethered(): self
    {
        return new self(self::CLASSIFICATION_TETHERED);
    }

    public function isRoot(): bool
    {
        return $this->value === self::CLASSIFICATION_ROOT;
    }

    public function isRegular(): bool
    {
        return $this->value === self::CLASSIFICATION_REGULAR;
    }

    public function isTethered(): bool
    {
        return $this->value === self::CLASSIFICATION_TETHERED;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(NodeAggregateClassification $other): bool
    {
        return $this->value === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
