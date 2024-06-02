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
 * @api
 */
final readonly class ContentStream
{
    /**
     * @internal
     */
    public function __construct(
        public ContentStreamId $id,
        public ?ContentStreamId $sourceContentStreamId,
        public ContentStreamStatus $status,
        public Version $version,
    ) {
    }
}
