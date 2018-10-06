<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\EventSourcing\Event\EventInterface;

final class ContentStreamWasForked implements EventInterface
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
     * @var integer
     */
    private $versionOfSourceContentStream;

    /**
     * ContentStreamWasForked constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param ContentStreamIdentifier $sourceContentStreamIdentifier
     * @param int $versionOfSourceContentStream
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, ContentStreamIdentifier $sourceContentStreamIdentifier, int $versionOfSourceContentStream)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->sourceContentStreamIdentifier = $sourceContentStreamIdentifier;
        $this->versionOfSourceContentStream = $versionOfSourceContentStream;
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

    /**
     * @return int
     */
    public function getVersionOfSourceContentStream(): int
    {
        return $this->versionOfSourceContentStream;
    }
}
