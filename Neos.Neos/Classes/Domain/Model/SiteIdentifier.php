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
final class SiteIdentifier implements \Stringable, CacheAwareInterface
{
    private function __construct(
        private readonly string $siteNodeName
    ) {
    }

    public static function fromSite(Site $site): self
    {
        return new self($site->getNodeName());
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
        return $this->siteNodeName === $other->siteNodeName;
    }

    public function __toString(): string
    {
        return 'SiteIdentifier: ' . $this->siteNodeName;
    }

    public function getValue(): string
    {
        return $this->siteNodeName;
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->siteNodeName;
    }

    public function asNodeName(): NodeName
    {
        return NodeName::fromString($this->siteNodeName);
    }
}
