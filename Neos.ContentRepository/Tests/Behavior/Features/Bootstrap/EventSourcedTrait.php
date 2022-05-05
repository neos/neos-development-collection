<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Security\Service\AuthorizationService;
use Neos\ContentRepository\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Feature\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamFinder;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\ContentStreamPruner;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\ContentStreamForking;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeCopying;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeCreation;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeDisabling;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeModification;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeMove;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeReferencing;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeRemoval;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeRenaming;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeTypeChange;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\NodeVariation;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\WorkspaceCreation;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\WorkspaceDiscarding;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features\WorkspacePublishing;
use Neos\ContentRepository\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\EventSourcedContentRepository\Tests\Behavior\Fixtures\PostalAddress;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\Flow\Configuration\ConfigurationManager;
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

    protected ContentGraphs $availableContentGraphs;

    protected ContentGraphs $activeContentGraphs;

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

    protected function getAvailableContentGraphs(): ContentGraphs
    {
        return $this->availableContentGraphs;
    }

    protected function getActiveContentGraphs(): ContentGraphs
    {
        return $this->activeContentGraphs;
    }

    protected function setupEventSourcedTrait()
    {
        $this->nodeAuthorizationService = $this->getObjectManager()->get(AuthorizationService::class);
        $this->nodeTypeManager = $this->getObjectManager()->get(NodeTypeManager::class);
        $configurationManager = $this->getObjectManager()->get(ConfigurationManager::class);

        $activeContentGraphsConfig = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.EventSourcedContentRepository.unstableInternalWillChangeLater.testing.activeContentGraphs'
        );
        $availableContentGraphs = [];
        foreach ($activeContentGraphsConfig as $name => $className) {
            if (is_string($className)) {
                $availableContentGraphs[$name] = $this->getObjectManager()->get($className);
            }
        }
        if (count($availableContentGraphs) === 0) {
            throw new \RuntimeException('No content graph active during testing. Please set one in settings in activeContentGraphs');
        }
        $this->availableContentGraphs = new ContentGraphs($availableContentGraphs);
        $this->workspaceFinder = $this->getObjectManager()->get(WorkspaceFinder::class);
        $this->nodeTypeConstraintFactory = $this->getObjectManager()->get(NodeTypeConstraintFactory::class);

        foreach ($configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.EventSourcedContentRepository.unstableInternalWillChangeLater.testing.projectorsToBeReset'
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
    public function beforeEventSourcedScenarioDispatcher(BeforeScenarioScope $scope)
    {
        $adapterTagPrefix = 'adapters=';
        $adapterTagPrefixLength = \mb_strlen($adapterTagPrefix);
        /** @var array<int,string> $adapterKeys */
        $adapterKeys = [];
        foreach ($scope->getFeature()->getTags() as $tagName) {
            if (\str_starts_with($tagName, $adapterTagPrefix)) {
                $adapterKeys = explode(',', \mb_substr($tagName, $adapterTagPrefixLength));
                break;
            }
        }

        $this->activeContentGraphs = count($adapterKeys) === 0
            ? $this->availableContentGraphs
            : $this->availableContentGraphs->reduceTo($adapterKeys);

        foreach ($this->getAvailableContentGraphs() as $contentGraph) {
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
            if (method_exists($projector, 'resetForTests')) {
                $projector->resetForTests();
            } else {
                $projector->reset();
            }
        }
    }

    /**
     * @param TableNode $payloadTable
     * @return array
     * @throws \Exception
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
                    $value = (string)$this->$propertyOrMethodName();
                } else {
                    // is property
                    $value = (string)$this->$propertyOrMethodName;
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

    /**
     * called by {@see readPayloadTable()} above, from RebasingAutoCreatedChildNodesWorks.feature
     * @return NodeAggregateIdentifier
     */
    protected function currentNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        $currentNodes = $this->currentNodes->getIterator()->getArrayCopy();
        $firstNode = reset($currentNodes);
        assert($firstNode instanceof NodeInterface);
        return $firstNode->getNodeAggregateIdentifier();
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
     */
    public function workspacesPointToDifferentContentStreams(string $rawWorkspaceNameA, string $rawWorkspaceNameB): void
    {
        $workspaceA = $this->workspaceFinder->findOneByName(WorkspaceName::fromString($rawWorkspaceNameA));
        Assert::assertInstanceOf(Workspace::class, $workspaceA, 'Workspace "' . $rawWorkspaceNameA . '" does not exist.');
        $workspaceB = $this->workspaceFinder->findOneByName(WorkspaceName::fromString($rawWorkspaceNameB));
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
     */
    public function workspaceDoesNotPointToContentStream(string $rawWorkspaceName, string $rawContentStreamIdentifier)
    {
        $workspace = $this->workspaceFinder->findOneByName(WorkspaceName::fromString($rawWorkspaceName));

        Assert::assertNotEquals($rawContentStreamIdentifier, (string)$workspace->getCurrentContentStreamIdentifier());
    }

    /**
     * @Then /^I expect the graph projection to consist of exactly (\d+) node(?:s)?$/
     * @param int $expectedNumberOfNodes
     */
    public function iExpectTheGraphProjectionToConsistOfExactlyNodes(int $expectedNumberOfNodes)
    {
        foreach ($this->getActiveContentGraphs() as $adapterName => $contentGraph) {
            $actualNumberOfNodes = $contentGraph->countNodes();
            Assert::assertSame($expectedNumberOfNodes, $actualNumberOfNodes, 'Content graph in adapter "' . $adapterName . '" consists of ' . $actualNumberOfNodes . ' nodes, expected were ' . $expectedNumberOfNodes . '.');
        }
    }

    /**
     * @Then /^the subtree for node aggregate "([^"]*)" with node types "([^"]*)" and (\d+) levels deep should be:$/
     */
    public function theSubtreeForNodeAggregateWithNodeTypesAndLevelsDeepShouldBe(
        string $serializedNodeAggregateIdentifier,
        string $serializedNodeTypeConstraints,
        int $maximumLevels,
        TableNode $table
    ): void {
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($serializedNodeAggregateIdentifier);
        $nodeTypeConstraints = $this->nodeTypeConstraintFactory->parseFilterString($serializedNodeTypeConstraints);
        foreach ($this->getActiveContentGraphs() as $adapterName => $contentGraph) {
            $expectedRows = $table->getHash();

            $subtree = $contentGraph
                ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
                ->findSubtrees(NodeAggregateIdentifiers::fromArray([$nodeAggregateIdentifier]), $maximumLevels, $nodeTypeConstraints);

            /** @var SubtreeInterface[] $flattenedSubtree */
            $flattenedSubtree = [];
            self::flattenSubtreeForComparison($subtree, $flattenedSubtree);

            Assert::assertEquals(count($expectedRows), count($flattenedSubtree), 'number of expected subtrees do not match (adapter: ' . $adapterName . ')');

            foreach ($expectedRows as $i => $expectedRow) {
                $expectedLevel = (int)$expectedRow['Level'];
                $actualLevel = $flattenedSubtree[$i]->getLevel();
                Assert::assertSame($expectedLevel, $actualLevel, 'Level does not match in index ' . $i . ', expected: ' . $expectedLevel . ', actual: ' . $actualLevel . ' (adapter: ' . $adapterName . ')');
                $expectedNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($expectedRow['NodeAggregateIdentifier']);
                $actualNodeAggregateIdentifier = $flattenedSubtree[$i]->getNode()->getNodeAggregateIdentifier();
                Assert::assertTrue($expectedNodeAggregateIdentifier->equals($actualNodeAggregateIdentifier), 'NodeAggregateIdentifier does not match in index ' . $i . ', expected: "' . $expectedNodeAggregateIdentifier . '", actual: "' . $actualNodeAggregateIdentifier . '" (adapter: ' . $adapterName . ')');
            }
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

        $contentGraphs = $this->getActiveContentGraphs()->getIterator()->getArrayCopy();
        $contentGraph = reset($contentGraphs);
        $sitesNodeAggregate = $contentGraph->findRootNodeAggregateByType($this->contentStreamIdentifier, \Neos\ContentRepository\SharedModel\NodeType\NodeTypeName::fromString('Neos.Neos:Sites'));
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
