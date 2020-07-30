<?php
namespace Neos\EventSourcedContentRepository\Command;

use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateRootWorkspace;
use Neos\EventSourcedContentRepository\Service\ContentStreamPruner;
use Neos\EventSourcing\Projection\ProjectionManager;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Cli\CommandController;
use Neos\Utility\ObjectAccess;

/**
 *
 */
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

    /**
     * @Flow\Inject
     * @var ContentStreamPruner
     */
    protected $contentStreamPruner;


    public function __construct(EventStore $contentRepositoryEventStore)
    {
        $this->contentRepositoryEventStore = $contentRepositoryEventStore;
        parent::__construct();
    }

    /**
     * @param string $contentStreamIdentifier
     * @param int $startSequenceNumber
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     */
    public function exportCommand(string $contentStreamIdentifier, int $startSequenceNumber = 0)
    {
        $events = $this->contentRepositoryEventStore->load(StreamName::fromString($contentStreamIdentifier), $startSequenceNumber);

        $normalizer = new EventNormalizer();

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
     * @param string $contentStreamIdentifier
     * @param string $file
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamAlreadyExists
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\WorkspaceAlreadyExists
     * @throws \Neos\EventSourcing\EventListener\Exception\EventCouldNotBeAppliedException
     */
    public function importCommand(string $contentStreamIdentifier, string $file = null)
    {
        if ($file !== null) {
            $fileStream = fopen($file, 'r');
            $this->outputLine('Reading from file: "%s"', [$file]);
        } else {
            $fileStream = fopen('php://stdin', 'r');
            $this->outputLine('Reading import data from standard in.');
        }

        $normalizer = new EventNormalizer();

        $contentStreamToImportTo = ContentStreamIdentifier::fromString($contentStreamIdentifier);
        $eventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamToImportTo)->getEventStreamName();

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
        $this->outputLine('Your events and projections are probably out of sync now, <error>make sure you replay all projections via "./flow projection:replayall"</error>.');
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
    public function pruneCommand()
    {
        $unusedContentStreams = $this->contentStreamPruner->prune();

        if (!count($unusedContentStreams)) {
            $this->outputLine('There are no unused content streams.');
        } else {
            foreach ($unusedContentStreams as $contentStream) {
                $this->outputFormatted('Removed %s', [$contentStream]);
            }
        }
    }


    /**
     * Remove unused and deleted content streams from the event stream; effectively REMOVING information completely
     */
    public function pruneRemovedFromEventStreamCommand()
    {
        $unusedContentStreams = $this->contentStreamPruner->pruneRemovedFromEventStream();

        if (!count($unusedContentStreams)) {
            $this->outputLine('There are no unused content streams.');
        } else {
            foreach ($unusedContentStreams as $contentStream) {
                $this->outputFormatted('Removed events for %s', [$contentStream]);
            }
        }
    }
}
