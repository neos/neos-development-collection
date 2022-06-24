<?php
declare(strict_types=1);

require_once(__DIR__ . '/../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
require_once(__DIR__ . '/../../../../Neos.ContentRepository/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepository\LegacyNodeMigration\NodeDataToEventsMigration;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\Flow\Property\PropertyMapper;
use PHPUnit\Framework\Assert;

/**
 * Features context
 */
class FeatureContext implements Context
{
    use FlowContextTrait;
    use NodeOperationsTrait;

    protected $isolated = false;

    private array $nodeDataRows = [];
    private array $events = [];

    private MigrationException|null $lastMigrationException = null;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();
    }

    /**
     * @AfterScenario
     */
    public function failIfLastMigrationThrewAException(): void
    {
        if ($this->lastMigrationException !== null) {
            Assert::fail(sprintf('The last migration run led to an exception: %s', $this->lastMigrationException->getMessage()));
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
                'identifier' => $row['Identifier'] ?? (string)NodeAggregateIdentifier::create(),
                'nodetype' => $row['Node Type'] ?? 'unstructured',
                'properties' => !empty($row['Properties']) ? $row['Properties'] : '{}',
                'dimensionvalues' => !empty($row['Dimension Values']) ? $row['Dimension Values'] : '{}',
                'hiddeninindex' => $row['Hidden in index'] ?? '0',
                'hidden' => $row['Hidden'] ?? '0',
            ];
        }, $nodeDataRows->getHash());
    }

    /**
     * @When I run the migration
     * @When I run the migration for content stream :contentStream
     */
    public function iRunTheMigration(string $contentStream = null): void
    {
        $nodeTypeManager = $this->getObjectManager()->get(NodeTypeManager::class);
        $propertyMapper = $this->getObjectManager()->get(PropertyMapper::class);
        $propertyConverter = $this->getObjectManager()->get(PropertyConverter::class);
        $contentDimensionSource = $this->getObjectManager()->get(ContentDimensionSourceInterface::class);
        $contentDimensionZookeeper = new ContentDimensionZookeeper($contentDimensionSource);
        $interDimensionalVariationGraph = new InterDimensionalVariationGraph($contentDimensionSource, $contentDimensionZookeeper);
        $migration = new NodeDataToEventsMigration($nodeTypeManager, $propertyMapper, $propertyConverter, $interDimensionalVariationGraph);
        if ($contentStream !== null) {
            $migration->setContentStreamIdentifier(ContentStreamIdentifier::fromString($contentStream));
        }
        try {
            $this->events = iterator_to_array($migration->run($this->nodeDataRows), false);
        } catch (MigrationException $exception) {
            $this->lastMigrationException = $exception;
        }
    }

    /**
     * @Then I expect the following events
     */
    public function iExpectTheFollowingEvents(TableNode $table): void
    {
        $expectedEvents = $table->getHash();
        foreach ($this->events as $event) {
            $expectedEventRow = array_shift($expectedEvents);
            if ($expectedEventRow === null) {
                Assert::assertCount(count($table->getHash()), $this->events, 'Expected number of events does not match actual number');
            }
            if (!empty($expectedEventRow['Type'])) {
                Assert::assertSame($expectedEventRow['Type'], (new \ReflectionClass($event))->getShortName(), 'Event payload: ' . json_encode($event));
            }
            try {
                $expectedEventPayload = json_decode($expectedEventRow['Payload'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new \RuntimeException(sprintf('Failed to decode expected JSON: %s', $expectedEventRow['Payload']), 1655811083);
            }
            $actualEventPayload = $this->getObjectManager()->get(EventNormalizer::class)->normalize($event);
            foreach (array_keys($actualEventPayload) as $key) {
                if (!array_key_exists($key, $expectedEventPayload)) {
                    unset($actualEventPayload[$key]);
                }
            }
            Assert::assertEquals($expectedEventPayload, $actualEventPayload, 'Actual event payload: ' . json_encode($actualEventPayload));
        }
        Assert::assertCount(count($table->getHash()), $this->events, 'Expected number of events does not match actual number');
    }

    /**
     * @Then I expect a MigrationException
     * @Then I expect a MigrationException with the message
     */
    public function iExpectAMigrationExceptionWithTheMessage(PyStringNode $expectedMessage = null): void
    {
        Assert::assertNotNull($this->lastMigrationException, 'Expected an MigrationException but none was thrown');
        if ($expectedMessage !== null) {
            Assert::assertSame($expectedMessage->getRaw(), $this->lastMigrationException->getMessage());
        }
        $this->lastMigrationException = null;
    }



}
