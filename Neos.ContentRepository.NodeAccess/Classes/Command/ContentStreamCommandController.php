<?php
namespace Neos\ContentRepository\NodeAccess\Command;

use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Service\ContentStreamPruner;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\Projection\ProjectionManager;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Cli\CommandController;
use Neos\Utility\ObjectAccess;

class ContentStreamCommandController extends CommandController
{
    /**
     * @var EventStore
     */
    private $contentRepositoryEventStore;

    /**
     * @Flow\Inject
     * @var ProjectionManager
     */
    protected $projectionManager;

    /**
     * @Flow\Inject
     * @var WorkspaceCommandHandler
     */
    protected $workspaceCommandHandler;


    public function __construct(EventStore $contentRepositoryEventStore)
    {
        $this->contentRepositoryEventStore = $contentRepositoryEventStore;
        parent::__construct();
    }

    /**
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function exportCommand(string $contentStreamIdentifier, int $startSequenceNumber = 0): void
    {
        $events = $this->contentRepositoryEventStore->load(
            StreamName::fromString($contentStreamIdentifier),
            $startSequenceNumber
        );

        $this->outputLine('[');
        $i = 0;
        foreach ($events as $eventEvelope) {
            $prepend = $i > 0 ? ',' : '';
            $properties = ObjectAccess::getGettableProperties($eventEvelope->getRawEvent());
            $this->outputLine(
                $prepend . json_encode($properties)
            );
            $i++;
        }
        $this->outputLine(']');
    }

    /**
     * Imports events to a content stream from the given file.
     * Note that the events in the file need to come from the same content stream you import to for now!
     *
     * @throws \Neos\ContentRepository\Feature\Common\Exception\ContentStreamAlreadyExists
     * @throws \Neos\ContentRepository\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists
     * @throws \Neos\EventSourcing\EventListener\Exception\EventCouldNotBeAppliedException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function importCommand(string $contentStreamIdentifier, string $file = null): void
    {
        if ($file !== null) {
            $fileStream = fopen($file, 'r');
            $this->outputLine('Reading from file: "%s"', [$file]);
        } else {
            $fileStream = fopen('php://stdin', 'r');
            $this->outputLine('Reading import data from standard in.');
        }
        if (!$fileStream) {
            throw new \InvalidArgumentException('Failed to open file ' . $file);
        }
        $normalizer = new EventNormalizer(new EventTypeResolver());

        $contentStreamToImportTo = ContentStreamIdentifier::fromString($contentStreamIdentifier);
        $eventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamToImportTo)
            ->getEventStreamName();

        $this->outputLine('Clearing workspace projection to create the workspace to import to.');
        $workspaceProjection = $this->projectionManager->getProjection('workspace');
        $this->projectionManager->replay($workspaceProjection->getIdentifier());

        $commandResult = $this->workspaceCommandHandler->handleCreateRootWorkspace(
            new CreateRootWorkspace(
                WorkspaceName::forLive(),
                WorkspaceTitle::fromString('Live'),
                WorkspaceDescription::fromString(''),
                UserIdentifier::forSystemUser(),
                $contentStreamToImportTo
            )
        );

        $this->outputLine('Created workspace "Live" for the given content stream identifier');

        $i = 0;
        $domainEvents = DomainEvents::createEmpty();

        $this->outputLine('starting import');
        $this->output->progressStart();
        for ($line = fgets($fileStream); $line !== false; $line = fgets($fileStream)) {
            $this->output->progressAdvance();
            $i++;
            if ($line === '[' || $line === ']') {
                continue;
            }

            $rawEventLine = ltrim($line, ',');
            $rawEventProperties = json_decode($rawEventLine, true);
            if (!is_array($rawEventProperties)) {
                continue;
            }

            $domainEvent = $normalizer->denormalize($rawEventProperties['payload'], $rawEventProperties['type']);
            $domainEvent = DecoratedEvent::addMetadata($domainEvent, $rawEventProperties['metadata']);

            $domainEvents = $domainEvents->appendEvent($domainEvent);

            if ($i === 10) {
                $this->contentRepositoryEventStore->commit($eventStreamName, $domainEvents);
                $domainEvents = DomainEvents::createEmpty();
                $i = 0;
            }
        }
        $this->output->progressFinish();

        $this->contentRepositoryEventStore->commit($eventStreamName, $domainEvents);
        fclose($fileStream);
        $this->outputLine('');
        $this->outputLine('Finished importing events.');
        $this->outputLine('Your events and projections are probably out of sync now,'
            . ' <error>make sure you replay all projections via "./flow projection:replayall"</error>.');
    }

    /**
     * Remove all content streams which are not needed anymore from the projections.
     *
     * NOTE: This still **keeps** the event stream as is; so it would be possible to re-construct the content stream
     *       at a later point in time (though we currently do not provide any API for it).
     *
     *       To remove the deleted Content Streams, use `./flow contentStream:pruneRemovedFromEventStream` after running
     *       `./flow contentStream:prune`.
     */
    public function pruneCommand(): void
    {
        $unusedContentStreams = $this->contentStreamPruner->prune();
        $unusedContentStreamsPresent = false;
        foreach ($unusedContentStreams as $contentStream) {
            $this->outputFormatted('Removed %s', [$contentStream]);
            $unusedContentStreamsPresent = true;
        }
        if (!$unusedContentStreamsPresent) {
            $this->outputLine('There are no unused content streams.');
        }
    }

    /**
     * Remove unused and deleted content streams from the event stream; effectively REMOVING information completely
     */
    public function pruneRemovedFromEventStreamCommand(): void
    {
        $unusedContentStreams = $this->contentStreamPruner->pruneRemovedFromEventStream();
        $unusedContentStreamsPresent = false;
        foreach ($unusedContentStreams as $contentStream) {
            $this->outputFormatted('Removed events for %s', [$contentStream]);
            $unusedContentStreamsPresent = true;
        }
        if (!$unusedContentStreamsPresent) {
            $this->outputLine('There are no unused content streams.');
        }
    }
}
