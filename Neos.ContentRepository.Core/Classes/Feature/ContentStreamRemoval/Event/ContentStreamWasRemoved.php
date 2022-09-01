<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * @api events are the persistence-API of the content repository
 */
final class ContentStreamWasRemoved implements EventInterface
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
