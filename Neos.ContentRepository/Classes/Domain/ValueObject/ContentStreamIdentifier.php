<?php

namespace Neos\ContentRepository\Domain\ValueObject;

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

final class ContentStreamIdentifier extends AbstractIdentifier implements CacheAwareInterface
{

    /**
     * @var ContentStreamIdentifier
     */
    private static $rootIdentifier;

    /**
     * the Root node is part of *every* content stream; thus the Root node is assigned a special
     * "root" content stream.
     *
     * @return ContentStreamIdentifier
     */
    public static function root(): ContentStreamIdentifier
    {
        if (!self::$rootIdentifier) {
            self::$rootIdentifier = new ContentStreamIdentifier('00000000-0000-0000-0000-000000000000');
        }
        return self::$rootIdentifier;
    }

    /**
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        return $this->uuid->toString();
    }

    public function isRoot(): bool
    {
        return $this === self::$rootIdentifier;
    }
}
