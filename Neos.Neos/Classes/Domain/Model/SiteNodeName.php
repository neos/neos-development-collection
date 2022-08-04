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

namespace Neos\Neos\Domain\Model;

use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\Flow\Annotations as Flow;

/**
 * This is the node name underneath "/sites" which uniquely identifies the specific site; and which is defined
 * by the user.
 *
 * @Flow\Proxy(false)
 */
final class SiteNodeName
{
    private function __construct(
        public readonly string $value
    ) {
    }

    public static function fromNodeName(NodeName $siteNodeName): self
    {
        return new self($siteNodeName->jsonSerialize());
    }

    public static function fromString(string $siteNodeName): self
    {
        return new self($siteNodeName);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toNodeName(): NodeName
    {
        return NodeName::fromString($this->value);
    }
}
