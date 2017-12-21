<?php

namespace Neos\ContentRepository\Domain\Context\ContentStream\Command;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;

/**
 * ForkContentStream for creating a new fork of a content stream.
 */
final class ForkContentStream
{

    /**
     * Content stream identifier for the new content stream
     *
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var ContentStreamIdentifier
     */
    private $sourceContentStreamIdentifier;

    /**
     * ContentStreamWasForked constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param ContentStreamIdentifier $sourceContentStreamIdentifier
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, ContentStreamIdentifier $sourceContentStreamIdentifier)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->sourceContentStreamIdentifier = $sourceContentStreamIdentifier;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getSourceContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->sourceContentStreamIdentifier;
    }
}
