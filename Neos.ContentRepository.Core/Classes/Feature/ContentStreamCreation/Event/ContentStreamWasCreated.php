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

namespace Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * ContentStreamWasCreated signifies the creation of the "ROOT" content streams.
 * All other content streams will be FORKED from this FIRST content stream.
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class ContentStreamWasCreated implements EventInterface, EmbedsContentStreamId
{
    public function __construct(
        public ContentStreamId $contentStreamId,
    ) {
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
