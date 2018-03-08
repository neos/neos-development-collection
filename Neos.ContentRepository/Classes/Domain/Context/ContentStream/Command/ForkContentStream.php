<?php
namespace Neos\ContentRepository\Domain\Context\ContentStream\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
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
