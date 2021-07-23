<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph as DbalContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph as PostgreSQLContentHypergraph;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamRepository;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\ContentStream\ContentStreamFinder;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\Workspace;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\ContentStreamPruner;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper\NodeDiscriminator;
use Neos\EventSourcedContentRepository\Tests\Behavior\Fixtures\PostalAddress;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Assert;

/**
 * Features context
 */
trait EventSourcedTrait
{
    use CurrentSubgraphTrait;
    use CurrentUserTrait;
    use ProjectedNodeAggregateTrait;
    use ProjectedNodeTrait;
    use GenericCommandExecutionAndEventPublication;

    use ContentStreamForking;

    use NodeCreation;
    use NodeCopying;
    use NodeDisabling;
    use NodeModification;
    use NodeMove;
    use NodeReferencing;
    use NodeRemoval;
    use NodeRenaming;
    use NodeTypeChange;
    use NodeVariation;

    use WorkspaceCreation;
    use WorkspaceDiscarding;
    use WorkspacePublishing;

    protected ContentGraphs $contentGraphs;

    /**
     * @var WorkspaceFinder
     */
    private $workspaceFinder;

    /**
     * @var NodeTypeConstraintFactory
     */
    private $nodeTypeConstraintFactory;

    protected ?NodeAggregateIdentifier $rootNodeAggregateIdentifier;

    /**
     * @var array|\Neos\EventSourcing\Projection\ProjectorInterface[]
     */
    private array $projectorsToBeReset = [];

    abstract protected function getObjectManager(): ObjectManagerInterface;

    protected function getWorkspaceFinder(): WorkspaceFinder
    {
        return $this->workspaceFinder;
    }

    protected function getContentGraphs(): ContentGraphs
    {
        return $this->contentGraphs;
    }

    protected function setupEventSourcedTrait()
    {
        $this->nodeAuthorizationService = $this->getObjectManager()->get(AuthorizationService::class);
        $this->nodeTypeManager = $this->getObjectManager()->get(NodeTypeManager::class);
        $this->contentGraphs = new ContentGraphs([
            'DoctrineDbal' => $this->getObjectManager()->get(DbalContentGraph::class),
            'PostgreSQL' => $this->getObjectManager()->get(PostgreSQLContentHypergraph::class)
        ]);
        $this->workspaceFinder = $this->getObjectManager()->get(WorkspaceFinder::class);
        $this->nodeTypeConstraintFactory = $this->getObjectManager()->get(NodeTypeConstraintFactory::class);

        $configurationManager = $this->getObjectManager()->get(\Neos\Flow\Configuration\ConfigurationManager::class);
        foreach ($configurationManager->getConfiguration(
            \Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.EventSourcedContentRepository.testing.projectorsToBeReset'
        ) ?: [] as $projectorClassName => $toBeReset) {
            if ($toBeReset) {
                $this->projectorsToBeReset[] = $this->getObjectManager()->get($projectorClassName);
            }
        }

        $contentStreamRepository = $this->getObjectManager()->get(ContentStreamRepository::class);
        ObjectAccess::setProperty($contentStreamRepository, 'contentStreams', [], true);
    }

    /**
     * @BeforeScenario
     * @return void
     * @throws \Exception
     */
    public function beforeEventSourcedScenarioDispatcher()
    {
        foreach ($this->getContentGraphs() as $contentGraph) {
            $contentGraph->enableCache();
        }
        $this->visibilityConstraints = VisibilityConstraints::frontend();
        $this->dimensionSpacePoint = null;
        $this->rootNodeAggregateIdentifier = null;
        $this->contentStreamIdentifier = null;
        $this->currentNodeAggregates = null;
        $this->currentUserIdentifier = null;
        $this->currentNodes = null;
        foreach ($this->projectorsToBeReset as $projector) {
            $projector->reset();
        }
    }

    /**
     * @param TableNode $payloadTable
     * @return array
     * @throws Exception
     */
    protected function readPayloadTable(TableNode $payloadTable): array
    {
        $eventPayload = [];
        foreach ($payloadTable->getHash() as $line) {
            if (strpos($line['Value'], '$this->') === 0) {
                // Special case: Referencing stuff from the context here
                $propertyOrMethodName = substr($line['Value'], strlen('$this->'));
                if (method_exists($this, $propertyOrMethodName)) {
                    // is method
                    $value = (string) $this->$propertyOrMethodName();
                } else {
                    // is property
                    $value = (string) $this->$propertyOrMethodName;
                }
            } else {
                // default case
                $value = json_decode($line['Value'], true);
                if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception(sprintf('The value "%s" is no valid JSON string', $line['Value']), 1546522626);
                }
            }
            $eventPayload[$line['Key']] = $value;
        }

        return $eventPayload;
    }

    protected function deserializeProperties(array $properties): PropertyValuesToWrite
    {
        foreach ($properties as &$propertyValue) {
            if ($propertyValue === 'PostalAddress:dummy') {
                $propertyValue = PostalAddress::dummy();
            } elseif ($propertyValue === 'PostalAddress:anotherDummy') {
                $propertyValue = PostalAddress::anotherDummy();
            }
            if (is_string($propertyValue)) {
                if (\mb_strpos($propertyValue, 'Date:') === 0) {
                    $propertyValue = \DateTimeImmutable::createFromFormat(\DateTimeInterface::W3C, \mb_substr($propertyValue, 5));
                } elseif (\mb_strpos($propertyValue, 'URI:') === 0) {
                    $propertyValue = new Uri(\mb_substr($propertyValue, 4));
                } elseif ($propertyValue === 'IMG:dummy') {
                    $propertyValue = $this->requireDummyImage();
                } elseif ($propertyValue === '[IMG:dummy]') {
                    $propertyValue = [$this->requireDummyImage()];
                }
            }
        }

        return PropertyValuesToWrite::fromArray($properties);
    }

    /**
     * @When /^the graph projection is fully up to date$/
     */
    public function theGraphProjectionIsFullyUpToDate()
    {
        if ($this->lastCommandOrEventResult === null) {
            throw new \RuntimeException('lastCommandOrEventResult not filled; so I cannot block!');
        }
        $this->lastCommandOrEventResult->blockUntilProjectionsAreUpToDate();
        $this->lastCommandOrEventResult = null;
    }

    /**
     * @Then /^workspace "([^"]*)" points to another content stream than workspace "([^"]*)"$/
     * @param string $rawWorkspaceNameA
     * @param string $rawWorkspaceNameB
     */
    public function workspacesPointToDifferentContentStreams(string $rawWorkspaceNameA, string $rawWorkspaceNameB)
    {
        $workspaceA = $this->workspaceFinder->findOneByName(new WorkspaceName($rawWorkspaceNameA));
        Assert::assertInstanceOf(Workspace::class, $workspaceA, 'Workspace "' . $rawWorkspaceNameA . '" does not exist.');
        $workspaceB = $this->workspaceFinder->findOneByName(new WorkspaceName($rawWorkspaceNameB));
        Assert::assertInstanceOf(Workspace::class, $workspaceB, 'Workspace "' . $rawWorkspaceNameB . '" does not exist.');
        if ($workspaceA && $workspaceB) {
            Assert::assertNotEquals(
                $workspaceA->getCurrentContentStreamIdentifier(),
                $workspaceB->getCurrentContentStreamIdentifier(),
                'Workspace "' . $rawWorkspaceNameA . '" points to the same content stream as "' . $rawWorkspaceNameB . '"'
            );
        }
    }

    /**
     * @Then /^workspace "([^"]*)" does not point to content stream "([^"]*)"$/
     * @param string $rawWorkspaceName
     * @param string $rawContentStreamIdentifier
     */
    public function workspaceDoesNotPointToContentStream(string $rawWorkspaceName, string $rawContentStreamIdentifier)
    {
        $workspace = $this->workspaceFinder->findOneByName(new WorkspaceName($rawWorkspaceName));

        Assert::assertNotEquals($rawContentStreamIdentifier, (string)$workspace->getCurrentContentStreamIdentifier());
    }

    /**
     * @Then /^I expect the graph projection to consist of exactly (\d+) node(?:s)?$/
     * @param int $expectedNumberOfNodes
     */
    public function iExpectTheGraphProjectionToConsistOfExactlyNodes(int $expectedNumberOfNodes)
    {
        foreach ($this->getContentGraphs() as $adapterName => $contentGraph) {
            $actualNumberOfNodes = $contentGraph->countNodes();
            Assert::assertSame($expectedNumberOfNodes, $actualNumberOfNodes, 'Content graph in adapter "' . $adapterName . '" consists of ' . $actualNumberOfNodes . ' nodes, expected were ' . $expectedNumberOfNodes . '.');
        }
    }

    /**
     * @Then /^the subtree for node aggregate "([^"]*)" with node types "([^"]*)" and (\d+) levels deep should be:$/
     * @param string $nodeAggregateIdentifier
     * @param string $nodeTypeConstraints
     * @param int $maximumLevels
     * @param TableNode $table
     */
    public function theSubtreeForNodeAggregateWithNodeTypesAndLevelsDeepShouldBe(string $nodeAggregateIdentifier, string $nodeTypeConstraints, int $maximumLevels, TableNode $table)
    {
        $expectedRows = $table->getHash();
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeAggregateIdentifier);
        $nodeTypeConstraints = $this->nodeTypeConstraintFactory->parseFilterString($nodeTypeConstraints);

        $subtree = $this->contentGraph
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findSubtrees([$nodeAggregateIdentifier], (int)$maximumLevels, $nodeTypeConstraints);

        /** @var SubtreeInterface[] $flattenedSubtree */
        $flattenedSubtree = [];
        self::flattenSubtreeForComparison($subtree, $flattenedSubtree);

        Assert::assertEquals(count($expectedRows), count($flattenedSubtree), 'number of expected subtrees do not match');

        foreach ($expectedRows as $i => $expectedRow) {
            $expectedLevel = (int)$expectedRow['Level'];
            $actualLevel = $flattenedSubtree[$i]->getLevel();
            Assert::assertSame($expectedLevel, $actualLevel, 'Level does not match in index ' . $i . ', expected: ' . $expectedLevel . ', actual: ' . $actualLevel);
            $expectedNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($expectedRow['NodeAggregateIdentifier']);
            $actualNodeAggregateIdentifier = $flattenedSubtree[$i]->getNode()->getNodeAggregateIdentifier();
            Assert::assertTrue($expectedNodeAggregateIdentifier->equals($actualNodeAggregateIdentifier), 'NodeAggregateIdentifier does not match in index ' . $i . ', expected: "' . $expectedNodeAggregateIdentifier . '", actual: "' . $actualNodeAggregateIdentifier . '"');
        }
    }

    private static function flattenSubtreeForComparison(SubtreeInterface $subtree, array &$result)
    {
        if ($subtree->getNode()) {
            $result[] = $subtree;
        }
        foreach ($subtree->getChildren() as $childSubtree) {
            self::flattenSubtreeForComparison($childSubtree, $result);
        }
    }

    /**
     * @var NodeAddress[]
     */
    private $currentNodeAddresses;

    /**
     * @param string|null $alias
     * @return NodeAddress
     */
    protected function getCurrentNodeAddress(string $alias = null): NodeAddress
    {
        if ($alias === null) {
            $alias = 'DEFAULT';
        }
        return $this->currentNodeAddresses[$alias];
    }

    /**
     * @return NodeAddress[]
     */
    public function getCurrentNodeAddresses(): array
    {
        return $this->currentNodeAddresses;
    }

    /**
     * @Given /^I get the node address for node aggregate "([^"]*)"(?:, remembering it as "([^"]*)")?$/
     * @param string $rawNodeAggregateIdentifier
     * @param string $alias
     */
    public function iGetTheNodeAddressForNodeAggregate(string $rawNodeAggregateIdentifier, $alias = 'DEFAULT')
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints);
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier);
        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAggregateIdentifier);
        Assert::assertNotNull($node, 'Did not find a node with aggregate identifier "' . $nodeAggregateIdentifier . '"');

        $this->currentNodeAddresses[$alias] = new NodeAddress(
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            $nodeAggregateIdentifier,
            $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($this->contentStreamIdentifier)->getWorkspaceName()
        );
    }

    /**
     * @Then /^I get the node address for the node at path "([^"]*)"(?:, remembering it as "([^"]*)")?$/
     * @param string $serializedNodePath
     * @param string $alias
     * @throws Exception
     */
    public function iGetTheNodeAddressForTheNodeAtPath(string $serializedNodePath, $alias = 'DEFAULT')
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints);
        if (!$this->getRootNodeAggregateIdentifier()) {
            throw new \Exception('ERROR: rootNodeAggregateIdentifier needed for running this step. You need to use "the event RootNodeAggregateWithNodeWasCreated was published with payload" to create a root node..');
        }
        $node = $subgraph->findNodeByPath(NodePath::fromString($serializedNodePath), $this->getRootNodeAggregateIdentifier());
        Assert::assertNotNull($node, 'Did not find a node at path "' . $serializedNodePath . '"');

        $this->currentNodeAddresses[$alias] = new NodeAddress(
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            $node->getNodeAggregateIdentifier(),
            $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($this->contentStreamIdentifier)->getWorkspaceName()
        );
    }

    protected function getWorkspaceCommandHandler(): WorkspaceCommandHandler
    {
        /** @var WorkspaceCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(WorkspaceCommandHandler::class);

        return $commandHandler;
    }

    protected function getContentStreamCommandHandler(): ContentStreamCommandHandler
    {
        /** @var ContentStreamCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(ContentStreamCommandHandler::class);

        return $commandHandler;
    }

    protected function getNodeAggregateCommandHandler(): NodeAggregateCommandHandler
    {
        /** @var NodeAggregateCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeAggregateCommandHandler::class);

        return $commandHandler;
    }

    protected function getNodeDuplicationCommandHandler(): NodeDuplicationCommandHandler
    {
        /** @var NodeDuplicationCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeDuplicationCommandHandler::class);

        return $commandHandler;
    }

    protected function getEventNormalizer(): EventNormalizer
    {
        /** @var EventNormalizer $eventNormalizer */
        $eventNormalizer = $this->getObjectManager()->get(EventNormalizer::class);

        return $eventNormalizer;
    }

    protected function getEventStore(): EventStore
    {
        /* @var EventStoreFactory $eventStoreFactory */
        $eventStoreFactory = $this->getObjectManager()->get(EventStoreFactory::class);

        return $eventStoreFactory->create('ContentRepository');
    }

    protected function getRuntimeBlocker(): RuntimeBlocker
    {
        /** @var RuntimeBlocker $runtimeBlocker */
        $runtimeBlocker = $this->getObjectManager()->get(RuntimeBlocker::class);

        return $runtimeBlocker;
    }

    protected function getRootNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        if ($this->rootNodeAggregateIdentifier) {
            return $this->rootNodeAggregateIdentifier;
        }

        $sitesNodeAggregate = $this->contentGraph->findRootNodeAggregateByType($this->contentStreamIdentifier, \Neos\ContentRepository\Domain\NodeType\NodeTypeName::fromString('Neos.Neos:Sites'));
        if ($sitesNodeAggregate) {
            return $sitesNodeAggregate->getIdentifier();
        }

        return null;
    }

    /**
     * @Then the content stream :contentStreamIdentifier has state :expectedState
     */
    public function theContentStreamHasState(string $contentStreamIdentifier, string $expectedState)
    {
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($contentStreamIdentifier);
        /** @var ContentStreamFinder $contentStreamFinder */
        $contentStreamFinder = $this->getObjectManager()->get(ContentStreamFinder::class);

        $actual = $contentStreamFinder->findStateForContentStream($contentStreamIdentifier);
        Assert::assertEquals($expectedState, $actual);
    }

    /**
     * @Then the current content stream has state :expectedState
     */
    public function theCurrentContentStreamHasState(string $expectedState)
    {
        $this->theContentStreamHasState($this->contentStreamIdentifier->jsonSerialize(), $expectedState);
    }

    /**
     * @When I prune unused content streams
     */
    public function iPruneUnusedContentStreams()
    {
        /** @var ContentStreamPruner $contentStreamPruner */
        $contentStreamPruner = $this->getObjectManager()->get(ContentStreamPruner::class);
        $contentStreamPruner->prune();
        $this->lastCommandOrEventResult = $contentStreamPruner->getLastCommandResult();
    }

    /**
     * @When I prune removed content streams from the event stream
     */
    public function iPruneRemovedContentStreamsFromTheEventStream()
    {
        /** @var ContentStreamPruner $contentStreamPruner */
        $contentStreamPruner = $this->getObjectManager()->get(ContentStreamPruner::class);
        $contentStreamPruner->pruneRemovedFromEventStream();
    }
}
