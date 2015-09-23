<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

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
     * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Model\Node
     */
    protected $rootNode;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var string
     */
    protected $currentTestWorkspaceName;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var Workspace
     */
    protected $liveWorkspace;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->currentTestWorkspaceName = uniqid('user-', true);

        $this->setUpRootNodeAndRepository();
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        $this->saveNodesAndTearDownRootNodeAndRepository();
        parent::tearDown();
    }

    protected function setUpRootNodeAndRepository()
    {
        $this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactory');
        $personalContext = $this->contextFactory->create(array('workspaceName' => $this->currentTestWorkspaceName));
        // Make sure the Workspace was created.
        $this->liveWorkspace = $personalContext->getWorkspace()->getBaseWorkspace();
        $this->nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
        $this->rootNode = $personalContext->getNode('/');

        $this->persistenceManager->persistAll();
    }

    protected function saveNodesAndTearDownRootNodeAndRepository()
    {
        if ($this->nodeDataRepository !== null) {
            $this->nodeDataRepository->flushNodeRegistry();
        }
        /** @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory $nodeFactory */
        $nodeFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Factory\NodeFactory');
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
        $this->assertSame($fooNode, $this->rootNode->getNode('foo'));

        $this->persistenceManager->persistAll();

        $this->assertSame($fooNode, $this->rootNode->getNode('foo'));
    }

    /**
     * @test
     */
    public function nodesCreatedInAPersonalWorkspaceAreNotVisibleInTheLiveWorkspace()
    {
        $this->rootNode->createNode('homepage')->createNode('about');

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $liveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $liveRootNode = $liveContext->getRootNode();

        $this->assertNull($liveRootNode->getNode('/homepage/about'));
    }

    /**
     * @test
     */
    public function evenWithoutPersistAllNodesCreatedInAPersonalWorkspaceAreNotVisibleInTheLiveWorkspace()
    {
        $this->rootNode->createNode('homepage')->createNode('imprint');

        $liveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $liveRootNode = $liveContext->getRootNode();

        $this->assertNull($liveRootNode->getNode('/homepage/imprint'));
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

        $this->assertSame($parentNode->getIdentifier(), $parentNode2->getIdentifier());
        $childNodeA2 = $parentNode2->getNode('child-node-a');
        $this->assertNotNull($childNodeA2, 'Child node A must be there');
        $childNodeB2 = $parentNode2->getNode('child-node-b');
        $this->assertNotNull($childNodeB2, 'Child node B must be there');
        $childNodeB2->moveInto($childNodeA2);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $parentNode3 = $this->rootNode->getNode('parent-node');
        //$this->assertNotSame($parentNode2, $parentNode3);
        $childNodeB3 = $parentNode3->getNode('child-node-b');
        $this->assertTrue($childNodeB3 === null, 'child node B should not shine through as it has been moved.');
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

        $this->assertNotSame($rootNode, $this->rootNode);
        $this->assertNotSame($rootNodeWorkspace, $this->rootNode->getWorkspace(), 'Workspace is not correctly cleaned up.');
        $parentNode2 = $this->rootNode->getNode('parent-node1');
        $this->assertNotSame($parentNode, $parentNode2);
        $this->assertSame('live', $parentNode2->getWorkspace()->getName());
        $childNodeA2 = $parentNode2->getNode('child-node-1a');
        $this->assertNotNull($childNodeA2, 'Child node A must be there');
        $this->assertNotSame($childNodeA, $childNodeA2);
        $childNodeB2 = $parentNode2->getNode('child-node-1b');
        $this->assertNotNull($childNodeB2, 'Child node B must be there');
        $this->assertNotSame($childNodeB, $childNodeB2);

        $childNodeB2->moveInto($childNodeA2);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $parentNode3 = $this->rootNode->getNode('parent-node1');
        $childNodes = $parentNode3->getChildNodes();
        $this->assertSame(1, count($childNodes), 'parent node is only allowed to have a single child node (child-node-1A).');
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
        $this->assertTrue($childNodeB3->getPrimaryChildNode() === null, 'Overlaid child node should be null');
        $childNodeA3 = $this->rootNode->getNode('parent-node/child-node-a');
        $childNodeC3 = $childNodeA3->getPrimaryChildNode();
        $this->assertNotNull($childNodeC3);
    }

    /**
     * @test
     */
    public function changedNodeCanBePublishedFromPersonalToLiveWorkspace()
    {
        $liveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $liveContext->getRootNode()->createNode('homepage')->createNode('teaser')->createNode('node52697bdfee199');

        $teaserNode = $this->rootNode->getNode('/homepage/teaser/node52697bdfee199');
        $teaserNode->setProperty('text', 'Updated text!');

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $teaserNode = $this->rootNode->getNode('/homepage/teaser/node52697bdfee199');
        $this->rootNode->getWorkspace()->publishNode($teaserNode, $this->liveWorkspace);

        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $liveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $liveRootNode = $liveContext->getRootNode();

        $teaserNode = $liveRootNode->getNode('/homepage/teaser/node52697bdfee199');

        $this->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\NodeInterface', $teaserNode);
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

        $liveContext = $this->contextFactory->create(array('workspaceName' => 'live', 'removedContentShown' => true));
        $liveRootNode = $liveContext->getRootNode();

        $liveHomepageNode = $liveRootNode->getNode('homepage');

        $this->assertTrue($liveHomepageNode === null, 'A removed node should be removed after publishing, but it was still found');
    }
}
