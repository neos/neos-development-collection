<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\ContentStream;

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
     * @param Command\CreateContentStream $command
     * @throws ContentStreamAlreadyExists
     */
    public function handleCreateContentStream(Command\CreateContentStream $command)
    {
        $this->requireContentStreamToNotExistYet($command->getContentStreamIdentifier());

        $this->eventPublisher->publish(
            ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier()),
            new Event\ContentStreamWasCreated(
                $command->getContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        );
    }

    /**
     * @param Command\ForkContentStream $command
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     */
    public function handleForkContentStream(Command\ForkContentStream $command)
    {
        $this->requireContentStreamToExist($command->getSourceContentStreamIdentifier());
        $this->requireContentStreamToNotExistYet($command->getContentStreamIdentifier());

        $sourceContentStream = $this->contentStreamRepository->findContentStream($command->getSourceContentStreamIdentifier());

        $this->eventPublisher->publish(
            ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier()),
            new Event\ContentStreamWasForked(
                $command->getContentStreamIdentifier(),
                $command->getSourceContentStreamIdentifier(),
                $sourceContentStream->getVersion()
            )
        );
    }


    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @throws ContentStreamAlreadyExists
     */
    protected function requireContentStreamToNotExistYet(ContentStreamIdentifier $contentStreamIdentifier)
    {
        if ($this->contentStreamRepository->findContentStream($contentStreamIdentifier)) {
            throw new ContentStreamAlreadyExists('Content stream "' . $contentStreamIdentifier . '" already exists.', 1521386345);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @throws ContentStreamDoesNotExistYet
     */
    protected function requireContentStreamToExist(ContentStreamIdentifier $contentStreamIdentifier)
    {
        if (!$this->contentStreamRepository->findContentStream($contentStreamIdentifier)) {
            throw new ContentStreamDoesNotExistYet('Content stream "' . $contentStreamIdentifier . '" does not exist yet.', 1521386692);
        }
    }
}
