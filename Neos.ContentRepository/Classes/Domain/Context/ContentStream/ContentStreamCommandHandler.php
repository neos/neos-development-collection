<?php

namespace Neos\ContentRepository\Domain\Context\ContentStream;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\ContentStream\Command\CreateContentStream;
use Neos\ContentRepository\Domain\Context\ContentStream\Command\ForkContentStream;
use Neos\ContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * ContentStreamCommandHandler
 */
final class ContentStreamCommandHandler
{
    /**
     * @Flow\Inject
     * @var \Neos\EventSourcing\Event\EventPublisher
     */
    protected $eventPublisher;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return string
     */
    public static function getStreamNameForContentStream(ContentStreamIdentifier $contentStreamIdentifier)
    {
        return 'Neos.ContentRepository:ContentStream:' . $contentStreamIdentifier;
    }

    /**
     * @param CreateContentStream $command
     */
    public function handleCreateContentStream(CreateContentStream $command)
    {
        $this->eventPublisher->publish(
            self::getStreamNameForContentStream($command->getContentStreamIdentifier()),
            new ContentStreamWasCreated(
                $command->getContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        );
    }

    /**
     * @param ForkContentStream $command
     */
    public function handleForkContentStream(ForkContentStream $command)
    {
        $this->eventPublisher->publish(
            self::getStreamNameForContentStream($command->getContentStreamIdentifier()),
            new ContentStreamWasForked(
                $command->getContentStreamIdentifier(),
                $command->getSourceContentStreamIdentifier()
            )
        );
    }
}
