<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * ForkContentStream for creating a new fork of a content stream.
 */
final class ForkContentStream
{
    /**
     * TODO: TargetContentStreamIdentifier??
     *
     * Content stream identifier for the new content stream
     *
     * @var ContentStreamIdentifier
     */
    private ContentStreamIdentifier $contentStreamIdentifier;

    private ContentStreamIdentifier $sourceContentStreamIdentifier;

    private UserIdentifier $initiatingUserIdentifier;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        ContentStreamIdentifier $sourceContentStreamIdentifier,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->sourceContentStreamIdentifier = $sourceContentStreamIdentifier;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            ContentStreamIdentifier::fromString($array['sourceContentStreamIdentifier']),
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getSourceContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->sourceContentStreamIdentifier;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
