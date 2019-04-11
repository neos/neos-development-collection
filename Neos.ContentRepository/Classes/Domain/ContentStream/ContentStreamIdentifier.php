<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Domain\ContentStream;

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
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Annotations as Flow;

/**
 * The ContentStreamIdentifier is the identifier for a Content Stream, which is
 * a central concept in the Event-Sourced CR introduced with Neos 5.0.
 *
 * @Flow\Proxy(false)
 */
final class ContentStreamIdentifier implements \JsonSerializable, CacheAwareInterface
{
    /**
     * @var string
     */
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new static($value);
    }

    public static function create(): self
    {
        return new static(Algorithms::generateUUID());
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(ContentStreamIdentifier $other): bool
    {
        return $this->value === $other->value;
    }
}
