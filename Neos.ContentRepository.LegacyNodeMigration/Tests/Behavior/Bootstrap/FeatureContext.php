<?php
declare(strict_types=1);

require_once(__DIR__ . '/../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentSubgraphTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentUserTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/CurrentDateTimeTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/ProjectedNodeAggregateTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/ProjectedNodeTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/GenericCommandExecutionAndEventPublication.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Core/Tests/Behavior/Features/Bootstrap/EventSourcedTrait.php');

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\EventSourcedTrait;
use Neos\ContentRepository\Export\Asset\AssetExporter;
use Neos\ContentRepository\Export\Asset\AssetLoaderInterface;
use Neos\ContentRepository\Export\Asset\ResourceLoaderInterface;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedResource;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvents;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\LegacyNodeMigration\NodeDataToAssetsProcessor;
use Neos\ContentRepository\LegacyNodeMigration\NodeDataToEventsProcessor;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\PersistentResource;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\Generator as MockGenerator;

/**
 * Features context
 */
class FeatureContext implements Context
{
    use FlowContextTrait;
    use NodeOperationsTrait;
    use EventSourcedTrait;

    protected $isolated = false;

    private array $nodeDataRows = [];
    /** @var array<PersistentResource> */
    private array $mockResources = [];
    /** @var array<SerializedAsset|SerializedImageVariant> */
    private array $mockAssets = [];
    private InMemoryFilesystemAdapter $mockFilesystemAdapter;
    private Filesystem $mockFilesystem;

    private ProcessorResult|null $lastMigrationResult = null;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();
        $this->mockFilesystemAdapter = new InMemoryFilesystemAdapter();
        $this->mockFilesystem = new Filesystem($this->mockFilesystemAdapter);
        $this->setupEventSourcedTrait();

    }

    /**
     * @AfterScenario
     */
    public function failIfLastMigrationHasErrors(): void
    {
        if ($this->lastMigrationResult !== null && $this->lastMigrationResult->severity === Severity::ERROR) {
            Assert::fail(sprintf('The last migration run led to an error: %s', $this->lastMigrationResult->message));
        }
    }

    /**
     * @When I have the following node data rows:
     */
    public function iHaveTheFollowingNodeDataRows(TableNode $nodeDataRows): void
    {
        $this->nodeDataRows = array_map(static function (array $row) {
            return [
                'path' => $row['Path'],
                'parentpath' => implode('/', array_slice(explode('/', $row['Path']), 0, -1)) ?: '/',
                'identifier' => $row['Identifier'] ?? (string)NodeAggregateId::create(),
                'nodetype' => $row['Node Type'] ?? 'unstructured',
                'properties' => !empty($row['Properties']) ? $row['Properties'] : '{}',
                'dimensionvalues' => !empty($row['Dimension Values']) ? $row['Dimension Values'] : '{}',
                'hiddeninindex' => $row['Hidden in index'] ?? '0',
                'hidden' => $row['Hidden'] ?? '0',
            ];
        }, $nodeDataRows->getHash());
    }

    /**
     * @When I run the event migration
     * @When I run the event migration for content stream :contentStream
     */
    public function iRunTheEventMigration(string $contentStream = null): void
    {
        $nodeTypeManager = $this->contentRepository->getNodeTypeManager();
        $propertyMapper = $this->getObjectManager()->get(PropertyMapper::class);
        $propertyConverter = $this->getContentRepositoryInternals()->propertyConverter;
        $interDimensionalVariationGraph = $this->getContentRepositoryInternals()->interDimensionalVariationGraph;

        $eventNormalizer = $this->getObjectManager()->get(EventNormalizer::class);
        $migration = new NodeDataToEventsProcessor($nodeTypeManager, $propertyMapper, $propertyConverter, $interDimensionalVariationGraph, $eventNormalizer, $this->mockFilesystem, $this->nodeDataRows);
        if ($contentStream !== null) {
            $migration->setContentStreamId(ContentStreamId::fromString($contentStream));
        }
        $this->lastMigrationResult = $migration->run();
    }

    /**
     * @Then I expect the following events to be exported
     */
    public function iExpectTheFollowingEventsToBeExported(TableNode $table): void
    {

        if (!$this->mockFilesystem->has('events.jsonl')) {
            Assert::fail('No events were exported');
        }
        $eventsJson = $this->mockFilesystem->read('events.jsonl');
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
            } catch (JsonException $e) {
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
     * @Then I expect a MigrationError
     * @Then I expect a MigrationError with the message
     */
    public function iExpectAMigrationErrorWithTheMessage(PyStringNode $expectedMessage = null): void
    {
        Assert::assertNotNull($this->lastMigrationResult, 'Expected the previous migration to contain errors, but no migration has been executed');
        Assert::assertSame(Severity::ERROR, $this->lastMigrationResult->severity, sprintf('Expected the previous migration to contain errors, but it ended with severity "%s"', $this->lastMigrationResult->severity->name));
        if ($expectedMessage !== null) {
            Assert::assertSame($expectedMessage->getRaw(), $this->lastMigrationResult->message);
        }
        $this->lastMigrationResult = null;
    }

    /**
     * @Given the following PersistentResources exist
     */
    public function theFollowingPersistentResourcesExist(TableNode $resources): void
    {
        foreach ($resources->getHash() as $resourceData) {
            $mockResource = (new MockGenerator())->getMock(PersistentResource::class, [], [], '', false);
            $mockResource->method('getFilename')->willReturn($resourceData['filename'] ?? 'filename');
            $mockResource->method('getCollectionName')->willReturn($resourceData['collectionName'] ?? 'persistent');
            $mockResource->method('getMediaType')->willReturn($resourceData['mediaType'] ?? 'image/jpeg');

            $contents = $resourceData['contents'] ?? $resourceData['identifier'];
            $mockResource->method('getSha1')->willReturn(sha1($contents));
            $stream = fopen('php://memory', 'rb+');
            fwrite($stream, $contents);
            rewind($stream);
            $mockResource->method('getStream')->willReturn($stream);
            $this->mockResources[$resourceData['identifier']] = $mockResource;
        }
    }

    /**
     * @Given the following Assets exist
     */
    public function theFollowingAssetsExist(TableNode $images): void
    {
        foreach ($images->getHash() as $assetData) {

            if (!isset($this->mockResources[$assetData['resourceId']])) {
                throw new \RuntimeException(sprintf('Resource id "%s" referenced in asset "%s" does not exist', $assetData['resourceId'], $assetData['identifier']));
            }
            $assetData['resource'] = SerializedResource::fromResource($this->mockResources[$assetData['resourceId']])->jsonSerialize();
            unset($assetData['resourceId']);
            $mockAsset = SerializedAsset::fromArray($assetData);
            $this->mockAssets[$mockAsset->identifier] = $mockAsset;
        }
    }

    /**
     * @Given the following ImageVariants exist
     */
    public function theFollowingImageVariantsExist(TableNode $imageVariants): void
    {
        foreach ($imageVariants->getHash() as $variantData) {
            try {
                $variantData['imageAdjustments'] = json_decode($variantData['imageAdjustments'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new \RuntimeException(sprintf('Failed to JSON decode imageAdjustments for variant "%s"', $variantData['identifier']), 1659530081, $e);
            }
            $variantData['width'] = (int)$variantData['width'];
            $variantData['height'] = (int)$variantData['height'];
            $mockImageVariant = SerializedImageVariant::fromArray($variantData);
            $this->mockAssets[$mockImageVariant->identifier] = $mockImageVariant;
        }
    }

    /**
     * @When I run the asset migration
     */
    public function iRunTheAssetMigration(): void
    {
        $nodeTypeManager = $this->getContentRepositoryInternals()->nodeTypeManager;
        $mockResourceLoader = new class ($this->mockResources) implements ResourceLoaderInterface {

            /**
             * @param array<PersistentResource> $mockResources
             */
            public function __construct(private array $mockResources) {}

            public function getStreamBySha1(string $sha1)
            {
                foreach ($this->mockResources as $mockResource) {
                    if ($mockResource->getSha1() === $sha1) {
                        return $mockResource->getStream();
                    }
                }
                throw new \InvalidArgumentException(sprintf('Mock resource with SHA1 "%s" does not exist', $sha1), 1659532905);
            }
        };

        $mockAssetLoader = new class ($this->mockAssets) implements AssetLoaderInterface {
            /**
             * @param array<SerializedAsset|SerializedImageVariant> $mockAssets
             */
            public function __construct(private array $mockAssets) {}

            public function findAssetById(string $assetId): SerializedAsset|SerializedImageVariant
            {
                if (!isset($this->mockAssets[$assetId])) {
                    throw new \InvalidArgumentException(sprintf('Failed to find mock asset with id "%s"', $assetId));
                }
                return $this->mockAssets[$assetId];
            }
        };

        $this->mockFilesystemAdapter->deleteEverything();
        $assetExporter = new AssetExporter($this->mockFilesystem, $mockAssetLoader, $mockResourceLoader);
        $migration = new NodeDataToAssetsProcessor($nodeTypeManager, $assetExporter, $this->nodeDataRows);
        $this->lastMigrationResult = $migration->run();
    }

    /**
     * @Then /^I expect the following (Assets|ImageVariants) to be exported:$/
     */
    public function iExpectTheFollowingToBeExported(string $type, PyStringNode $expectedAssets): void
    {
        $actualAssets = [];
        if (!$this->mockFilesystem->directoryExists($type)) {
            Assert::fail(sprintf('No %1$s have been exported (Directory "/%1$s" does not exist)', $type));
        }
        /** @var FileAttributes $file */
        foreach ($this->mockFilesystem->listContents($type) as $file) {
            $actualAssets[] = json_decode($this->mockFilesystem->read($file->path()), true, 512, JSON_THROW_ON_ERROR);
        }
        Assert::assertJsonStringEqualsJsonString($expectedAssets->getRaw(), json_encode($actualAssets, JSON_THROW_ON_ERROR));
    }

    /**
     * @Then /^I expect no (Assets|ImageVariants) to be exported$/
     */
    public function iExpectNoAssetsToBeExported(string $type): void
    {
        Assert::assertFalse($this->mockFilesystem->directoryExists($type));
    }

    /**
     * @Then I expect the following PersistentResources to be exported:
     */
    public function iExpectTheFollowingPersistentResourcesToBeExported(TableNode $expectedResources): void
    {
        $actualResources = [];
        if (!$this->mockFilesystem->directoryExists('Resources')) {
            Assert::fail('No PersistentResources have been exported (Directory "/Resources" does not exist)');
        }
        /** @var FileAttributes $file */
        foreach ($this->mockFilesystem->listContents('Resources') as $file) {
            $actualResources[] = ['Filename' => basename($file->path()), 'Contents' => $this->mockFilesystem->read($file->path())];
        }
        Assert::assertSame($expectedResources->getHash(), $actualResources);
    }

    /**
     * @Then /^I expect no PersistentResources to be exported$/
     */
    public function iExpectNoPersistentResourcesToBeExported(): void
    {
        Assert::assertFalse($this->mockFilesystem->directoryExists('Resources'));
    }


    /** ---------------------------------- */

    /**
     * @param TableNode $table
     * @return array
     * @throws JsonException
     */
    private function parseJsonTable(TableNode $table): array
    {
        return array_map(static function (array $row) {
            return array_map(static function (string $jsonValue) {
                return json_decode($jsonValue, true, 512, JSON_THROW_ON_ERROR);
            }, $row);
        }, $table->getHash());
    }
}
