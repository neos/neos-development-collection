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

require_once(__DIR__ . '/Features/ContentStreamForking.php');
require_once(__DIR__ . '/Features/NodeCopying.php');
require_once(__DIR__ . '/Features/NodeCreation.php');
require_once(__DIR__ . '/Features/NodeDisabling.php');
require_once(__DIR__ . '/Features/NodeModification.php');
require_once(__DIR__ . '/Features/NodeMove.php');
require_once(__DIR__ . '/Features/NodeReferencing.php');
require_once(__DIR__ . '/Features/NodeRemoval.php');
require_once(__DIR__ . '/Features/NodeRenaming.php');
require_once(__DIR__ . '/Features/NodeTypeChange.php');
require_once(__DIR__ . '/Features/NodeVariation.php');
require_once(__DIR__ . '/Features/WorkspaceCreation.php');
require_once(__DIR__ . '/Features/WorkspaceDiscarding.php');
require_once(__DIR__ . '/Features/WorkspacePublishing.php');

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintParser;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Security\Service\AuthorizationService;
use Neos\ContentRepository\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Feature\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
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
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Helpers\ContentRepositoryInternals;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Helpers\ContentRepositoryInternalsFactory;
use Neos\ContentRepository\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\Tests\Behavior\Fixtures\PostalAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
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
     * @var NodeTypeConstraintParser
     */
    private $nodeTypeConstraintFactory;

    protected ?NodeAggregateIdentifier $rootNodeAggregateIdentifier;

    private ContentRepositoryIdentifier $contentRepositoryIdentifier;
    private ContentRepositoryRegistry $contentRepositoryRegistry;
    private ContentRepository $contentRepository;
    private ContentRepositoryInternals $contentRepositoryInternals;

    abstract protected function getObjectManager(): ObjectManagerInterface;


    protected function getContentRepositoryIdentifier(): ContentRepositoryIdentifier
    {
        return $this->contentRepositoryIdentifier;
    }

    protected function getContentRepositoryRegistry(): ContentRepositoryRegistry
    {
        return $this->contentRepositoryRegistry;
    }

    protected function getContentRepository(): ContentRepository
    {
        return $this->contentRepository;
    }

    /**
     * @return ContentRepositoryInternals
     * @deprecated ideally we would not need this in tests
     */
    protected function getContentRepositoryInternals(): ContentRepositoryInternals
    {
        return $this->contentRepositoryInternals;
    }

    /**
     * @return WorkspaceFinder
     * @deprecated
     */
    protected function getWorkspaceFinder(): WorkspaceFinder
    {
        return $this->getContentRepository()->getWorkspaceFinder();
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
        $configurationManager = $this->getObjectManager()->get(ConfigurationManager::class);

        $this->contentRepositoryIdentifier = ContentRepositoryIdentifier::fromString('default');
        $this->contentRepositoryRegistry = $this->getObjectManager()->get(ContentRepositoryRegistry::class);
        $this->initCleanContentRepository();
    }

    private function initCleanContentRepository(): void
    {
        $this->contentRepositoryRegistry->forgetInstances();
        $this->contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryIdentifier);
        $this->contentRepository->setUp(); // TODO: is this too slow for every test??
        $this->contentRepositoryInternals = $this->contentRepositoryRegistry->getService($this->contentRepositoryIdentifier, new ContentRepositoryInternalsFactory());

        $availableContentGraphs = [];
        $availableContentGraphs['DoctrineDBAL'] = $this->contentRepository->getContentGraph();
        $availableContentGraphs['Postgres'] = null; // TODO: currently disabled

        if (count($availableContentGraphs) === 0) {
            throw new \RuntimeException('No content graph active during testing. Please set one in settings in activeContentGraphs');
        }
        $this->availableContentGraphs = new ContentGraphs($availableContentGraphs);
        $this->nodeTypeConstraintFactory = NodeTypeConstraintParser::create($this->contentRepository->getNodeTypeManager());

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


        $this->visibilityConstraints = VisibilityConstraints::frontend();
        $this->dimensionSpacePoint = null;
        $this->rootNodeAggregateIdentifier = null;
        $this->contentStreamIdentifier = null;
        $this->currentNodeAggregates = null;
        $this->currentUserIdentifier = null;
        $this->currentNodes = null;
        $this->contentRepository->resetProjectionStates();

        $connection = $this->objectManager->get(DbalClientInterface::class)->getConnection();
        // copied from DoctrineEventStoreFactory
        $eventTableName = sprintf('neos_cr_%s_events', $this->contentRepositoryIdentifier);
        $connection->executeStatement('TRUNCATE ' . $eventTableName);

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
                } else {
                    try {
                        $propertyValue = \json_decode($propertyValue, true, 512, JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        // then don't, just keep the value
                    }
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
        $this->lastCommandOrEventResult->block();
        $this->lastCommandOrEventResult = null;
    }

    /**
     * @Then /^workspace "([^"]*)" points to another content stream than workspace "([^"]*)"$/
     */
    public function workspacesPointToDifferentContentStreams(string $rawWorkspaceNameA, string $rawWorkspaceNameB): void
    {
        $workspaceA = $this->contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($rawWorkspaceNameA));
        Assert::assertInstanceOf(Workspace::class, $workspaceA, 'Workspace "' . $rawWorkspaceNameA . '" does not exist.');
        $workspaceB = $this->contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($rawWorkspaceNameB));
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
        $workspace = $this->contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($rawWorkspaceName));

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
    ): void
    {
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
            $this->contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamIdentifier($this->contentStreamIdentifier)->getWorkspaceName()
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
            $this->contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamIdentifier($this->contentStreamIdentifier)->getWorkspaceName()
        );
    }

    /**
     * @return EventStoreInterface
     * @deprecated
     */
    protected function getEventStore(): EventStoreInterface
    {
        return $this->getContentRepositoryInternals()->eventStore;
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
        $contentStreamFinder = $this->getContentRepository()->getContentStreamFinder();

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
        $this->contentRepositoryFactory->
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
