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

use Neos\EventSourcing\Event\EventPublisher;

/**
 * ContentStreamCommandHandler
 */
final class ContentStreamCommandHandler
{
    /**
     * @var ContentStreamRepository
     */
    protected $contentStreamRepository;

    /**
     * @var EventPublisher
     */
    protected $eventPublisher;


    public function __construct(ContentStreamRepository $contentStreamRepository, EventPublisher $eventPublisher)
    {
        $this->contentStreamRepository = $contentStreamRepository;
        $this->eventPublisher = $eventPublisher;
    }


    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return string
     * @deprecated fetch a content stream and ask it instead
     */
    public static function getStreamNameForContentStream(ContentStreamIdentifier $contentStreamIdentifier)
    {
        return 'Neos.ContentRepository:ContentStream:' . $contentStreamIdentifier;
    }

    public function handleCreateContentStream(Command\CreateContentStream $command)
    {
        $contentStream = $this->contentStreamRepository->getContentStream($command->getContentStreamIdentifier());
        $this->eventPublisher->publish(
            $contentStream->getStreamName(),
            new Event\ContentStreamWasCreated(
                $command->getContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        );
    }

    public function handleForkContentStream(Command\ForkContentStream $command)
    {
        $sourceContentStream = $this->contentStreamRepository->getContentStream($command->getSourceContentStreamIdentifier());
        $contentStream = $this->contentStreamRepository->getContentStream($command->getContentStreamIdentifier());

        $this->eventPublisher->publish(
            $contentStream->getStreamName(),
            new Event\ContentStreamWasForked(
                $command->getContentStreamIdentifier(),
                $command->getSourceContentStreamIdentifier(),
                $sourceContentStream->getVersion()
            )
        );
    }
}
