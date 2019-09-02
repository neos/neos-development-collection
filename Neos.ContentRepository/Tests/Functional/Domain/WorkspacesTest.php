<?php
namespace Neos\ContentRepository\Tests\Functional\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

/**
 * Functional test case which covers all workspace-related behavior of the
 * content repository.
 *
 */
class WorkspacesTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var Node
     */
    protected $rootNode;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var string
     */
    protected $currentTestWorkspaceName;

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
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->currentTestWorkspaceName = uniqid('user-', true);

        $this->setUpRootNodeAndRepository();
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        $this->saveNodesAndTearDownRootNodeAndRepository();
        parent::tearDown();
    }

    protected function setUpRootNodeAndRepository()
    {
        $this->contextFactory = $this->objectManager->get(ContextFactory::class);
        $personalContext = $this->contextFactory->create(['workspaceName' => $this->currentTestWorkspaceName]);

        $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        if ($this->liveWorkspace === null) {
            $this->liveWorkspace = new Workspace('live');
            $this->workspaceRepository->add($this->liveWorkspace);
            $this->workspaceRepository->add(new Workspace($this->currentTestWorkspaceName, $this->liveWorkspace));
            $this->persistenceManager->persistAll();
        }

        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        $this->rootNode = $personalContext->getNode('/');

        $this->persistenceManager->persistAll();
    }

    protected function saveNodesAndTearDownRootNodeAndRepository()
    {
        if ($this->nodeDataRepository !== null) {
            $this->nodeDataRepository->flushNodeRegistry();
        }
        /** @var NodeFactory $nodeFactory */
        $nodeFactory = $this->objectManager->get(NodeFactory::class);
        $nodeFactory->reset();
        $this->contextFactory->reset();

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->nodeDataRepository = null;
        $this->rootNode = null;
    }

    /**
     * @test
     */
    public function nodesCreatedInAPersonalWorkspacesCanBeRetrievedAgainInThePersonalContext()
    {
        $fooNode = $this->rootNode->createNode('foo');
        self::assertSame($fooNode, $this->rootNode->getNode('foo'));

        $this->persistenceManager->persistAll();

        self::assertSame($fooNode, $this->rootNode->getNode('foo'));
    }

    /**
     * @test
     */
    public function nodesCreatedInAPersonalWorkspaceAreNotVisibleInTheLiveWorkspace()
    {
        $this->rootNode->createNode('homepage')->createNode('about');

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $liveContext = $this->contextFactory->create(['workspaceName' => 'live']);
        $liveRootNode = $liveContext->getRootNode();

        self::assertNull($liveRootNode->getNode('/homepage/about'));
    }

    /**
     * @test
     */
    public function evenWithoutPersistAllNodesCreatedInAPersonalWorkspaceAreNotVisibleInTheLiveWorkspace()
    {
        $this->rootNode->createNode('homepage')->createNode('imprint');

        $liveContext = $this->contextFactory->create(['workspaceName' => 'live']);
        $liveRootNode = $liveContext->getRootNode();

        self::assertNull($liveRootNode->getNode('/homepage/imprint'));
    }

    /**
     * We set up the following node structure:
     *
     * rootNode
     *     |
     *   parentNode
     *  |          |
     * child-node-a  child-node-b
     *               |
     *             child-node-c
     *
     * We then move child-node-b UNDERNEATH child-node-a and check that it does not shine through
     * when directly asking parentNode for child-node-b.
     *
     * @test
     */
    public function nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldBeRemovedFromOriginalLocation()
    {
        $parentNode = $this->rootNode->createNode('parent-node');
        $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB->createNode('child-node-c');
        $this->persistenceManager->persistAll();
        $parentNode->getWorkspace()->publish($this->liveWorkspace);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $parentNode2 = $this->rootNode->getNode('parent-node');

        self::assertSame($parentNode->getIdentifier(), $parentNode2->getIdentifier());
        $childNodeA2 = $parentNode2->getNode('child-node-a');
        self::assertNotNull($childNodeA2, 'Child node A must be there');
        $childNodeB2 = $parentNode2->getNode('child-node-b');
        self::assertNotNull($childNodeB2, 'Child node B must be there');
        $childNodeB2->moveInto($childNodeA2);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $parentNode3 = $this->rootNode->getNode('parent-node');
        //self::assertNotSame($parentNode2, $parentNode3);
        $childNodeB3 = $parentNode3->getNode('child-node-b');
        self::assertTrue($childNodeB3 === null, 'child node B should not shine through as it has been moved.');
    }

    /**
     * For test setup / node structure, see nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldBeRemovedFromOriginalLocation
     *
     * @test
     */
    public function nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldBeRemovedFromOriginalLocationWhileIteratingOverIt()
    {
        $rootNode = $this->rootNode;
        $rootNodeWorkspace = $this->rootNode->getWorkspace();
        $parentNode = $this->rootNode->createNode('parent-node1');
        $childNodeA = $parentNode->createNode('child-node-1a');
        $childNodeB = $parentNode->createNode('child-node-1b');
        $childNodeB->createNode('child-node-1c');
        $this->persistenceManager->persistAll();
        $parentNode->getWorkspace()->publish($this->liveWorkspace);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        self::assertNotSame($rootNode, $this->rootNode);
        self::assertNotSame($rootNodeWorkspace, $this->rootNode->getWorkspace(), 'Workspace is not correctly cleaned up.');
        $parentNode2 = $this->rootNode->getNode('parent-node1');
        self::assertNotSame($parentNode, $parentNode2);
        self::assertSame('live', $parentNode2->getWorkspace()->getName());
        $childNodeA2 = $parentNode2->getNode('child-node-1a');
        self::assertNotNull($childNodeA2, 'Child node A must be there');
        self::assertNotSame($childNodeA, $childNodeA2);
        $childNodeB2 = $parentNode2->getNode('child-node-1b');
        self::assertNotNull($childNodeB2, 'Child node B must be there');
        self::assertNotSame($childNodeB, $childNodeB2);

        $childNodeB2->moveInto($childNodeA2);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $parentNode3 = $this->rootNode->getNode('parent-node1');
        $childNodes = $parentNode3->getChildNodes();
        self::assertSame(1, count($childNodes), 'parent node is only allowed to have a single child node (child-node-1A).');
    }

    /**
     * For test setup / node structure, see nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldBeRemovedFromOriginalLocation
     *
     * Here, we move child-node-c underneath child-node-a.
     *
     * @test
     */
    public function nodesWhichAreMovedAcrossLevelsAndWorkspacesShouldWorkWhenUsingPrimaryChildNode()
    {
        $parentNode = $this->rootNode->createNode('parent-node');
        $parentNode->createNode('child-node-a');
        $childNodeB = $parentNode->createNode('child-node-b');
        $childNodeB->createNode('child-node-c');
        $parentNode->getWorkspace()->publish($this->liveWorkspace);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $childNodeC2 = $this->rootNode->getNode('parent-node/child-node-b/child-node-c');
        $childNodeA2 = $this->rootNode->getNode('parent-node/child-node-a');
        $childNodeC2->moveInto($childNodeA2);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $childNodeB3 = $this->rootNode->getNode('parent-node/child-node-b');
        self::assertTrue($childNodeB3->getPrimaryChildNode() === null, 'Overlaid child node should be null');
        $childNodeA3 = $this->rootNode->getNode('parent-node/child-node-a');
        $childNodeC3 = $childNodeA3->getPrimaryChildNode();
        self::assertNotNull($childNodeC3);
    }

    /**
     * @test
     */
    public function changedNodeCanBePublishedFromPersonalToLiveWorkspace()
    {
        $liveContext = $this->contextFactory->create(['workspaceName' => 'live']);
        $liveContext->getRootNode()->createNode('homepage')->createNode('teaser')->createNode('node52697bdfee199');

        $teaserNode = $this->rootNode->getNode('/homepage/teaser/node52697bdfee199');
        $teaserNode->setProperty('text', 'Updated text!');

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $this->rootNode->getWorkspace()->publishNode($teaserNode, $this->liveWorkspace);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $liveContext = $this->contextFactory->create(['workspaceName' => 'live']);
        $liveRootNode = $liveContext->getRootNode();

        $teaserNode = $liveRootNode->getNode('/homepage/teaser/node52697bdfee199');

        self::assertInstanceOf(NodeInterface::class, $teaserNode);
    }

    /**
     * @test
     */
    public function removedNodeWithoutExistingTargetNodeDataWillBeRemovedWhenPublished()
    {
        $homepageNode = $this->rootNode->createNode('homepage');
        $homepageNode->remove();

        $this->rootNode->getWorkspace()->publish($this->liveWorkspace);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $liveContext = $this->contextFactory->create(['workspaceName' => 'live', 'removedContentShown' => true]);
        $liveRootNode = $liveContext->getRootNode();

        $liveHomepageNode = $liveRootNode->getNode('homepage');

        self::assertTrue($liveHomepageNode === null, 'A removed node should be removed after publishing, but it was still found');
    }
}
