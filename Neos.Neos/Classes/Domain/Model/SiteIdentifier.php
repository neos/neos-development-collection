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


final class SiteIdentifier implements \Stringable
{
    private function __construct(
        private readonly string $siteNodeName
    ) {
    }

    public static function fromSite(Site $site): self
    {
        return new self($site->getNodeName());
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
}
