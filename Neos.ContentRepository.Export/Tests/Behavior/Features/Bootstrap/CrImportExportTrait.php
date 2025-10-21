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
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Neos\ContentRepository\Core\EventStore\InitiatingEventMetadata;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvents;
use Neos\ContentRepository\Export\Factory\EventExportProcessorFactory;
use Neos\ContentRepository\Export\Factory\EventStoreImportProcessorFactory;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
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

    private \Throwable|null $crImportExportTrait_lastMigrationException = null;

    /** @var array<string> */
    private array $crImportExportTrait_loggedErrors = [];

    /** @var array<string> */
    private array $crImportExportTrait_loggedWarnings = [];

    private function setupCrImportExportTrait(): void
    {
        $this->crImportExportTrait_filesystem = new Filesystem(new InMemoryFilesystemAdapter());
    }

    /**
     * @AfterScenario
     */
    public function failIfLastMigrationHasErrors(): void
    {
        if ($this->crImportExportTrait_lastMigrationException !== null) {
            throw new \RuntimeException(sprintf('The last migration run led to an exception: %s', $this->crImportExportTrait_lastMigrationException->getMessage()));
        }
        if ($this->crImportExportTrait_loggedErrors !== []) {
            throw new \RuntimeException(sprintf('The last migration run logged %d error%s', count($this->crImportExportTrait_loggedErrors), count($this->crImportExportTrait_loggedErrors) === 1 ? '' : 's'));
        }
    }

    private function runCrImportExportProcessors(ProcessorInterface ...$processors): void
    {
        $processingContext = new ProcessingContext($this->crImportExportTrait_filesystem, function (Severity $severity, string $message) {
            if ($severity === Severity::ERROR) {
                $this->crImportExportTrait_loggedErrors[] = $message;
            } elseif ($severity === Severity::WARNING) {
                $this->crImportExportTrait_loggedWarnings[] = $message;
            }
        });
        foreach ($processors as $processor) {
            assert($processor instanceof ProcessorInterface);
            try {
                $processor->run($processingContext);
            } catch (\Throwable $e) {
                $this->crImportExportTrait_lastMigrationException = $e;
                break;
            }
        }
    }

    /**
     * @When /^the events are exported$/
     */
    public function theEventsAreExported(): void
    {
        $eventExporter = $this->getContentRepositoryService(new EventExportProcessorFactory($this->currentContentRepository->findWorkspaceByName(WorkspaceName::forLive())->currentContentStreamId));
        assert($eventExporter instanceof EventExportProcessor);
        $this->runCrImportExportProcessors($eventExporter);
    }

    /**
     * @When /^I import the events\.jsonl(?: into workspace "([^"]*)")?$/
     */
    public function iImportTheEventsJsonl(?string $workspace = null): void
    {
        $workspaceName = $workspace !== null ? WorkspaceName::fromString($workspace) : $this->currentWorkspaceName;
        $eventImporter = $this->getContentRepositoryService(new EventStoreImportProcessorFactory($workspaceName, true));
        assert($eventImporter instanceof EventStoreImportProcessor);
        $this->runCrImportExportProcessors($eventImporter);
    }

    /**
     * @Given /^using the following events\.jsonl:$/
     */
    public function usingTheFollowingEventsJsonl(PyStringNode $string): void
    {
        $this->crImportExportTrait_filesystem->write('events.jsonl', $string->getRaw());
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
            // we have to remove the event ids to make the events diff-able
            $eventsWithoutRandomIds[] = $exportedEvent
                ->withIdentifier('random-event-uuid');
        }

        Assert::assertSame($string->getRaw(), ExportedEvents::fromIterable($eventsWithoutRandomIds)->toJsonl());
    }

    /**
     * @Then I expect the following events to be exported
     */
    public function iExpectTheFollowingEventsToBeExported(TableNode $table): void
    {

        if (!$this->crImportExportTrait_filesystem->has('events.jsonl')) {
            Assert::fail('No events were exported');
        }
        $eventsJson = $this->crImportExportTrait_filesystem->read('events.jsonl');
        $exportedEvents = iterator_to_array(ExportedEvents::fromJsonl($eventsJson));

        $expectedEvents = $table->getHash();
        foreach ($exportedEvents as $exportedEvent) {
            $expectedEventRow = array_shift($expectedEvents);
            if ($expectedEventRow === null) {
                Assert::assertCount(count($table->getHash()), $exportedEvents, 'Expected number of events does not match actual number');
            }
            if (!empty($expectedEventRow['Type'])) {
                Assert::assertSame($expectedEventRow['Type'], $exportedEvent->type, 'Event: ' . $exportedEvent->toJson());
            }
            try {
                $expectedEventPayload = json_decode($expectedEventRow['Payload'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException(sprintf('Failed to decode expected JSON: %s', $expectedEventRow['Payload']), 1655811083);
            }
            $actualEventPayload = $exportedEvent->payload;
            foreach (array_keys($actualEventPayload) as $key) {
                if (!array_key_exists($key, $expectedEventPayload)) {
                    unset($actualEventPayload[$key]);
                }
            }
            Assert::assertEquals($expectedEventPayload, $actualEventPayload, 'Actual event: ' . $exportedEvent->toJson());
        }
        Assert::assertCount(count($table->getHash()), $exportedEvents, 'Expected number of events does not match actual number');
    }

    /**
     * @Then I expect the following sites to be exported
     */
    public function iExpectTheFollowingSitesToBeExported(TableNode $table): void
    {
        if (!$this->crImportExportTrait_filesystem->has('sites.json')) {
            Assert::fail('No events were exported');
        }
        $actualSitesJson = $this->crImportExportTrait_filesystem->read('sites.json');
        $actualSiteRows = json_decode($actualSitesJson, true, 512, JSON_THROW_ON_ERROR);

        $expectedSites = $table->getHash();
        foreach ($expectedSites as $key => $expectedSiteData) {
            $actualSiteData = $actualSiteRows[$key] ?? [];
            $expectedSiteData = array_map(
                fn(string $value) => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
                $expectedSiteData
            );
            Assert::assertEquals($expectedSiteData, $actualSiteData, 'Actual site: ' . json_encode($actualSiteData, JSON_THROW_ON_ERROR));
        }
        Assert::assertCount(count($table->getHash()), $actualSiteRows, 'Expected number of sites does not match actual number');
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
     * @Then I expect a migration exception
     * @Then I expect a migration exception with the message
     */
    public function iExpectAMigrationExceptionWithTheMessage(?PyStringNode $expectedMessage = null): void
    {
        Assert::assertNotNull($this->crImportExportTrait_lastMigrationException, 'Expected the previous migration to lead to an exception, but no exception was thrown');
        if ($expectedMessage !== null) {
            Assert::assertSame($expectedMessage->getRaw(), $this->crImportExportTrait_lastMigrationException->getMessage());
        }
        $this->crImportExportTrait_lastMigrationException = null;
    }

    /**
     * @Given the following ImageVariants exist
     */
    public function theFollowingImageVariantsExist(TableNode $imageVariants): void
    {
        foreach ($imageVariants->getHash() as $variantData) {
            try {
                $variantData['imageAdjustments'] = json_decode($variantData['imageAdjustments'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException(sprintf('Failed to JSON decode imageAdjustments for variant "%s"', $variantData['identifier']), 1659530081, $e);
            }
            $variantData['width'] = (int)$variantData['width'];
            $variantData['height'] = (int)$variantData['height'];
            $mockImageVariant = SerializedImageVariant::fromArray($variantData);
            $this->mockAssets[$mockImageVariant->identifier] = $mockImageVariant;
        }
    }

    /**
     * @Then /^I expect the following (Assets|ImageVariants) to be exported:$/
     */
    public function iExpectTheFollowingAssetsOrImageVariantsToBeExported(string $type, PyStringNode $expectedAssets): void
    {
        $actualAssets = [];
        if (!$this->crImportExportTrait_filesystem->directoryExists($type)) {
            Assert::fail(sprintf('No %1$s have been exported (Directory "/%1$s" does not exist)', $type));
        }
        /** @var FileAttributes $file */
        foreach ($this->crImportExportTrait_filesystem->listContents($type) as $file) {
            $actualAssets[] = json_decode($this->crImportExportTrait_filesystem->read($file->path()), true, 512, JSON_THROW_ON_ERROR);
        }
        Assert::assertJsonStringEqualsJsonString($expectedAssets->getRaw(), json_encode($actualAssets, JSON_THROW_ON_ERROR));
    }


    /**
     * @Then /^I expect no (Assets|ImageVariants) to be exported$/
     */
    public function iExpectNoAssetsToBeExported(string $type): void
    {
        Assert::assertFalse($this->crImportExportTrait_filesystem->directoryExists($type));
    }

    /**
     * @Then I expect the following PersistentResources to be exported:
     */
    public function iExpectTheFollowingPersistentResourcesToBeExported(TableNode $expectedResources): void
    {
        $actualResources = [];
        if (!$this->crImportExportTrait_filesystem->directoryExists('Resources')) {
            Assert::fail('No PersistentResources have been exported (Directory "/Resources" does not exist)');
        }
        /** @var FileAttributes $file */
        foreach ($this->crImportExportTrait_filesystem->listContents('Resources') as $file) {
            $actualResources[] = ['Filename' => basename($file->path()), 'Contents' => $this->crImportExportTrait_filesystem->read($file->path())];
        }
        Assert::assertSame($expectedResources->getHash(), $actualResources);
    }

    /**
     * @Then /^I expect no PersistentResources to be exported$/
     */
    public function iExpectNoPersistentResourcesToBeExported(): void
    {
        Assert::assertFalse($this->crImportExportTrait_filesystem->directoryExists('Resources'));
    }
}
