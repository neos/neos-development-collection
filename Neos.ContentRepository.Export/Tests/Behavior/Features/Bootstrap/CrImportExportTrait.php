<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap;

use Behat\Gherkin\Node\PyStringNode;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjectionFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvents;
use Neos\ContentRepository\Export\Processors\EventExportProcessor;
use Neos\ContentRepository\Export\Processors\EventStoreImportProcessor;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use PHPUnit\Framework\Assert;

/**
 * @todo move this class somewhere where its autoloaded
 */
trait CrImportExportTrait
{
    use CRTestSuiteRuntimeVariables;

    private Filesystem $crImportExportTrait_filesystem;

    public function setupCrImportExportTrait()
    {
        $this->crImportExportTrait_filesystem = new Filesystem(new InMemoryFilesystemAdapter());
    }

    /**
     * @When /^the events are exported i expect the following jsonl:$/
     */
    public function theEventsAreExportedIExpectTheFollowingJsonl(PyStringNode $string)
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());

        $eventExporter = $this->getContentRepositoryService(
            new class ($filesystem) implements ContentRepositoryServiceFactoryInterface {
                public function __construct(private readonly Filesystem $filesystem)
                {
                }
                public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): EventExportProcessor {
                    return new EventExportProcessor(
                        $this->filesystem,
                        $serviceFactoryDependencies->contentRepository->getWorkspaceFinder(),
                        $serviceFactoryDependencies->eventStore
                    );
                }
            }
        );
        assert($eventExporter instanceof EventExportProcessor);
        $eventExporter->run();

        $jsonL = $filesystem->read('events.jsonl');

        $exportedEvents = ExportedEvents::fromJsonl($jsonL);
        $eventsWithoutRandomIds = [];

        foreach ($exportedEvents as $exportedEvent) {
            // we have to remove the event id in \Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher::enrichWithCommand
            // and the initiatingTimestamp to make the events diff able
            $eventsWithoutRandomIds[] = $exportedEvent
                ->withIdentifier('random-event-uuid')
                ->processMetadata(function (array $metadata) {
                    $metadata['initiatingTimestamp'] = 'random-time';
                    return $metadata;
                });
        }

        Assert::assertSame($string->getRaw(), ExportedEvents::fromIterable($eventsWithoutRandomIds)->toJsonl());
    }



    /**
     * @When /^I import the events\.jsonl(?: into "([^"]*)")?$/
     */
    public function iImportTheFollowingJson(?string $contentStreamId = null)
    {
        $eventImporter = $this->getContentRepositoryService(
            new class ($this->crImportExportTrait_filesystem, $contentStreamId ? ContentStreamId::fromString($contentStreamId) : null) implements ContentRepositoryServiceFactoryInterface {
                public function __construct(
                    private readonly Filesystem $filesystem,
                    private readonly ?ContentStreamId $contentStreamId
                ) {
                }
                public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): EventStoreImportProcessor {
                    return new EventStoreImportProcessor(
                        false,
                        $this->filesystem,
                        $serviceFactoryDependencies->eventStore,
                        $serviceFactoryDependencies->eventNormalizer,
                        $this->contentStreamId
                    );
                }
            }
        );
        assert($eventImporter instanceof EventStoreImportProcessor);
        $result = $eventImporter->run();

        Assert::assertSame($result->severity, Severity::NOTICE);
    }

    /**
     * @Given /^using the following events\.jsonl:$/
     */
    public function usingTheFollowingEventsJsonl(PyStringNode $string)
    {
        $this->crImportExportTrait_filesystem->write('events.jsonl', $string->getRaw());
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    protected function getTableNamePrefix(): string
    {
        return DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix(
            $this->currentContentRepository->id
        );
    }
}
