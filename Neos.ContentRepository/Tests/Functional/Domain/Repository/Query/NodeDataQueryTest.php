<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Functional\Domain\Repository\Query;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Repository\Query\NodeCreationDateTimeFilter;
use Neos\ContentRepository\Domain\Repository\Query\NodeIdentifierFilter;
use Neos\ContentRepository\Domain\Repository\Query\NodeLastPublicationDateTimeFilter;
use Neos\ContentRepository\Domain\Repository\Query\NodeLastPublicationDateTimeOrder;
use Neos\ContentRepository\Domain\Repository\Query\NodeNameFilter;
use Neos\ContentRepository\Domain\Repository\Query\NodeDataQuery;
use Neos\ContentRepository\Domain\Repository\Query\NodeParentFilter;
use Neos\ContentRepository\Domain\Repository\Query\NodePathOrder;
use Neos\ContentRepository\Domain\Repository\Query\NodeTypeFilter;
use Neos\ContentRepository\Domain\Repository\Query\NodePropertyFilter;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Functional test case.
 */
class NodeDataQueryTest extends FunctionalTestCase
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var Workspace
     */
    protected $liveWorkspace;

    /**
     * @return void
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function setUp()
    {
        parent::setUp();
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->liveWorkspace = new Workspace('live');
        $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        $this->workspaceRepository->add($this->liveWorkspace);
        $this->persistenceManager->persistAll();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $this->context = $this->contextFactory->create(['workspaceName' => 'live']);
        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', []);
    }

    protected function setUpNodes()
    {
        $rootNode = $this->context->getRootNode();
        $rootNode
            ->createNode('node-1')
            ->createNode('node-1-1')
            ->setProperty('property-x-x', '1-1');
        $rootNode
            ->createNode('node-2')
            ->createNode('node-2-1')
            ->setProperty('property-x-x', '2-1');
        $node_3 = $rootNode
            ->createNode('node-3', $this->nodeTypeManager->getNodeType('Neos.NodeTypes:Page'), '554f9350-f02b-fec4-e1bf-e1bcaf9ebc77');
        $node_3->setProperty('foo', ['bar' => ['baz' => 123]]);
        $node_4 = $rootNode
            ->createNode('node-4', $this->nodeTypeManager->getNodeType('Neos.NodeTypes:Page'), 'e9b1a63b-9c8b-fce7-eeab-59d76a255881');
        $node_4->setProperty('foo', ['bar' => ['baz' => 456]]);
        $rootNode
            ->createNode('node-5')
            ->createNode('node-5-1')
            ->createNode('node-5-1-1')
            ->createNode('node-5-1-1-1');

        $node_6 = $rootNode->createNode('node-6');

        /** @var Node $node_6_1 */
        $node_6_1 = $node_6->createNode('node-6-1');
        $node_6_1->setProperty('foo', 1);
        $node_6_1->setProperty('bar', 1);
        $node_6_1->setLastPublicationDateTime(new \DateTime('2000-06-01T01:00:00+00:00'));

        /** @var Node $node_6_2 */
        $node_6_2 = $node_6->createNode('node-6-2');
        $node_6_2->setProperty('foo', 1);
        $node_6_2->setProperty('bar', 2);
        $node_6_2->setLastPublicationDateTime(new \DateTime('2000-06-01T02:00:00+00:00'));

        /** @var Node $node_6_3 */
        $node_6_3 = $node_6->createNode('node-6-3');
        $node_6_3->setProperty('foo', 1);
        $node_6_3->setProperty('bar', 3);
        $node_6_3->setLastPublicationDateTime(new \DateTime('2000-06-01T03:00:00+00:00'));

        $this->persistenceManager->persistAll();
    }

    /**
     * @return array
     */
    public function filterDataProvider()
    {
        return [
            'nodeType' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodeTypeFilter($nodeTypeManager->getNodeType('Neos.NodeTypes:Page')));
                $query->order(new NodePathOrder());
            }, ['node-3', 'node-4'], null],
            'nodeTypeAndLimit' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodeTypeFilter($nodeTypeManager->getNodeType('Neos.NodeTypes:Page')));
                $query->order(new NodePathOrder());
            }, ['node-3'], 1],
            'nodeName' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodeNameFilter('node-2-1'));
            }, ['node-2-1'], null],
            'property' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodePropertyFilter('property-x-x', '1-1'));
            }, ['node-1-1'], null],
            /*subproperty search does not yet work on PostgreSQL
            'subproperty' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodePropertyFilter('foo.bar.baz', 123));
            }, ['node-3'], null],*/
            'property>=' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodePropertyFilter('foo', 1));
                $query->filter(new NodePropertyFilter('bar', 2, '>='));
                $query->order(new NodePathOrder());
            }, ['node-6-2', 'node-6-3'], null],
            'property!=' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodePropertyFilter('foo', 1));
                $query->filter(new NodePropertyFilter('bar', 2, '!='));
                $query->order(new NodePathOrder());
            }, ['node-6-1', 'node-6-3'], null],
            'property<=' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodePropertyFilter('foo', 1));
                $query->filter(new NodePropertyFilter('bar', 2, '<='));
                $query->order(new NodePathOrder());
            }, ['node-6-1', 'node-6-2'], null],
            'property>' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodePropertyFilter('foo', 1));
                $query->filter(new NodePropertyFilter('bar', 2, '>'));
            }, ['node-6-3'], null],
            'property<' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodePropertyFilter('foo', 1));
                $query->filter(new NodePropertyFilter('bar', 2, '<'));
            }, ['node-6-1'], null],
            'identifier' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodeIdentifierFilter('554f9350-f02b-fec4-e1bf-e1bcaf9ebc77'));
            }, ['node-3'], null],
            'parent' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodeParentFilter(new NodeData('/node-2', $workspace)));
            }, ['node-2-1'], null],
            'parentrecursive' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodeParentFilter(new NodeData('/node-5/node-5-1', $workspace), true));
                $query->order(new NodePathOrder());
            }, ['node-5-1-1', 'node-5-1-1-1'], null],
            'lastPublicationDateTime' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $query->filter(new NodeParentFilter(new NodeData('/node-6', $workspace), true));
                $query->filter(new NodeLastPublicationDateTimeFilter(new \DateTime('2000-06-01T03:00:00+00:00'), '<'));
                $query->order(new NodeLastPublicationDateTimeOrder());
            }, ['node-6-1', 'node-6-2'], null],
            'creationDateTime' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $tenSecondsAgo = new \DateTime('now');
                $tenSecondsAgo->sub(new \DateInterval('PT10S'));

                $inTenSeconds = new \DateTime('now');
                $inTenSeconds->add(new \DateInterval('PT10S'));

                $query->filter(new NodeParentFilter(new NodeData('/node-6', $workspace), true));
                $query->filter(new NodeCreationDateTimeFilter($tenSecondsAgo, '>='));
                $query->filter(new NodeCreationDateTimeFilter($inTenSeconds, '<='));

                $query->order(new NodeLastPublicationDateTimeOrder());
            }, ['node-6-1', 'node-6-2', 'node-6-3'], null],
            'creationDateTime2' => [function (NodeDataQuery $query, NodeTypeManager $nodeTypeManager, Workspace $workspace) {
                $tenSecondsAgo = new \DateTime('now');
                $tenSecondsAgo->sub(new \DateInterval('PT10S'));

                $inTenSeconds = new \DateTime('now');
                $inTenSeconds->add(new \DateInterval('PT10S'));

                $query->filter(new NodeParentFilter(new NodeData('/node-6', $workspace), true));
                $query->filter(new NodeCreationDateTimeFilter($tenSecondsAgo, '<='));
                $query->filter(new NodeCreationDateTimeFilter($inTenSeconds, '>='));

                $query->order(new NodeLastPublicationDateTimeOrder());
            }, [], null],
        ];
    }

    /**
     * @test
     * @dataProvider filterDataProvider
     */
    public function queryGet($queryBuilderFunc, $expectedNodes, $limit)
    {
        $this->setUpNodes();

        $query = new NodeDataQuery($this->context->getWorkspace());
        $this->inject($query, 'nodeDataRepository', $this->nodeDataRepository);
        $queryBuilderFunc($query, $this->nodeTypeManager, $this->liveWorkspace);

        $actualNodes = [];
        /** @var NodeData $node */
        foreach ($query->get($limit) as $node) {
            $actualNodes[] = $node->getName();
        }
        $this->assertEquals($expectedNodes, $actualNodes);
    }

    /**
     * @test
     * @dataProvider filterDataProvider
     */
    public function queryCount($queryBuilderFunc, $expectedNodes, $limit)
    {
        $this->setUpNodes();

        $query = new NodeDataQuery($this->context->getWorkspace());
        $this->inject($query, 'nodeDataRepository', $this->nodeDataRepository);
        $queryBuilderFunc($query, $this->nodeTypeManager, $this->liveWorkspace);

        $this->assertEquals(count($expectedNodes), $query->count($limit));
    }
}
