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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

use Neos\EventStore\Model\Event\Version;

/**
 * Content Stream Read Model
 *
 * @internal the public API is limited to the {@see Workspace} read model
 */
final readonly class ContentStream
{
    private function __construct(
        public ContentStreamId $id,
        public ?ContentStreamId $sourceContentStreamId,
        public Version $version,
        public bool $isClosed
    ) {
    }

    public static function create(
        ContentStreamId $id,
        ?ContentStreamId $sourceContentStreamId,
        Version $version,
        bool $isClosed
    ): self {
        return new self(
            $id,
            $sourceContentStreamId,
            $version,
            $isClosed
        );
    }
}
