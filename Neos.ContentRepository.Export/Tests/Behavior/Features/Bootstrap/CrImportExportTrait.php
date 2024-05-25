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
use Behat\Gherkin\Node\TableNode;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvents;
use Neos\ContentRepository\Export\ProcessorResult;
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

    private ?ProcessorResult $crImportExportTrait_lastMigrationResult = null;

    /** @var array<string> */
    private array $crImportExportTrait_loggedErrors = [];

    /** @var array<string> */
    private array $crImportExportTrait_loggedWarnings = [];

    public function setupCrImportExportTrait()
    {
        $this->crImportExportTrait_filesystem = new Filesystem(new InMemoryFilesystemAdapter());
    }

    /**
     * @When /^the events are exported$/
     */
    public function theEventsAreExportedIExpectTheFollowingJsonl()
    {
        $eventExporter = $this->getContentRepositoryService(
            new class ($this->crImportExportTrait_filesystem) implements ContentRepositoryServiceFactoryInterface {
                public function __construct(private readonly Filesystem $filesystem)
                {
                }
                public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): EventExportProcessor {
                    return new EventExportProcessor(
                        $this->filesystem,
                        $serviceFactoryDependencies->contentRepository->findWorkspaceByName(WorkspaceName::forLive())->currentContentStreamId,
                        $serviceFactoryDependencies->eventStore
                    );
                }
            }
        );
        assert($eventExporter instanceof EventExportProcessor);

        $eventExporter->onMessage(function (Severity $severity, string $message) {
            if ($severity === Severity::ERROR) {
                $this->crImportExportTrait_loggedErrors[] = $message;
            } elseif ($severity === Severity::WARNING) {
                $this->crImportExportTrait_loggedWarnings[] = $message;
            }
        });
        $this->crImportExportTrait_lastMigrationResult = $eventExporter->run();
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

        $eventImporter->onMessage(function (Severity $severity, string $message) {
            if ($severity === Severity::ERROR) {
                $this->crImportExportTrait_loggedErrors[] = $message;
            } elseif ($severity === Severity::WARNING) {
                $this->crImportExportTrait_loggedWarnings[] = $message;
            }
        });
        $this->crImportExportTrait_lastMigrationResult = $eventImporter->run();
    }

    /**
     * @Given /^using the following events\.jsonl:$/
     */
    public function usingTheFollowingEventsJsonl(PyStringNode $string)
    {
        $this->crImportExportTrait_filesystem->write('events.jsonl', $string->getRaw());
    }

    /**
     * @AfterScenario
     */
    public function failIfLastMigrationHasErrors(): void
    {
        if ($this->crImportExportTrait_lastMigrationResult !== null && $this->crImportExportTrait_lastMigrationResult->severity === Severity::ERROR) {
            throw new \RuntimeException(sprintf('The last migration run led to an error: %s', $this->crImportExportTrait_lastMigrationResult->message));
        }
        if ($this->crImportExportTrait_loggedErrors !== []) {
            throw new \RuntimeException(sprintf('The last migration run logged %d error%s', count($this->crImportExportTrait_loggedErrors), count($this->crImportExportTrait_loggedErrors) === 1 ? '' : 's'));
        }
    }

    /**
     * @Then I expect the following jsonl:
     */
    public function iExpectTheFollowingJsonL(PyStringNode $string): void
    {
        if (!$this->crImportExportTrait_filesystem->has('events.jsonl')) {
            Assert::fail('No events were exported');
        }

        $jsonL = $this->crImportExportTrait_filesystem->read('events.jsonl');

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
     * @Then I expect the following errors to be logged
     */
    public function iExpectTheFollowingErrorsToBeLogged(TableNode $table): void
    {
        Assert::assertSame($table->getColumn(0), $this->crImportExportTrait_loggedErrors, 'Expected logged errors do not match');
        $this->crImportExportTrait_loggedErrors = [];
    }

    /**
     * @Then I expect the following warnings to be logged
     */
    public function iExpectTheFollowingWarningsToBeLogged(TableNode $table): void
    {
        Assert::assertSame($table->getColumn(0), $this->crImportExportTrait_loggedWarnings, 'Expected logged warnings do not match');
        $this->crImportExportTrait_loggedWarnings = [];
    }

    /**
     * @Then I expect a MigrationError
     * @Then I expect a MigrationError with the message
     */
    public function iExpectAMigrationErrorWithTheMessage(PyStringNode $expectedMessage = null): void
    {
        Assert::assertNotNull($this->crImportExportTrait_lastMigrationResult, 'Expected the previous migration to contain errors, but no migration has been executed');
        Assert::assertSame(Severity::ERROR, $this->crImportExportTrait_lastMigrationResult->severity, sprintf('Expected the previous migration to contain errors, but it ended with severity "%s"', $this->crImportExportTrait_lastMigrationResult->severity->name));
        if ($expectedMessage !== null) {
            Assert::assertSame($expectedMessage->getRaw(), $this->crImportExportTrait_lastMigrationResult->message);
        }
        $this->crImportExportTrait_lastMigrationResult = null;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;
}
