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

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * ContentStreamWasCreated signifies the creation of the "ROOT" content streams.
 * All other content streams will be FORKED from this FIRST content stream.
 *
 * @api events are the persistence-API of the content repository
 */
final class ContentStreamWasCreated implements EventInterface
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly UserId $initiatingUserId
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            UserId::fromString($values['initiatingUserId']),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'initiatingUserId' => $this->initiatingUserId,
        ];
    }
}
