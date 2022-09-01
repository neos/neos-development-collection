<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\ContentStreamForking\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\Version;

/**
 * @api events are the persistence-API of the content repository
 */
final class ContentStreamWasForked implements EventInterface
{
    public function __construct(
        /**
         * Content stream identifier for the new content stream
         */
        public readonly ContentStreamId $newContentStreamId,
        public readonly ContentStreamId $sourceContentStreamId,
        public readonly Version $versionOfSourceContentStream,
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['newContentStreamId']),
            ContentStreamId::fromString($values['sourceContentStreamId']),
            Version::fromInteger($values['versionOfSourceContentStream']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
