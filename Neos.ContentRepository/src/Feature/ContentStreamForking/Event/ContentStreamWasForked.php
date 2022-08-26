<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\ContentStreamForking\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\EventStore\EventInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
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
        public readonly ContentStreamIdentifier $newContentStreamIdentifier,
        public readonly ContentStreamIdentifier $sourceContentStreamIdentifier,
        public readonly Version $versionOfSourceContentStream,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamIdentifier::fromString($values['contentStreamIdentifier']),
            ContentStreamIdentifier::fromString($values['sourceContentStreamIdentifier']),
            Version::fromInteger($values['versionOfSourceContentStream']),
            UserIdentifier::fromString($values['initiatingUserIdentifier']),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->newContentStreamIdentifier,
            'sourceContentStreamIdentifier' => $this->sourceContentStreamIdentifier,
            'versionOfSourceContentStream' => $this->versionOfSourceContentStream->value,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
        ];
    }
}
