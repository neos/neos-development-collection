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
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

/**
 * Functional test case which covers all workspace-related behavior
 * for layered workspaces.
 *
 * Tests use a structure like:
 *
 * - live
 *   - group workspace
 *     - user workspace
 */
class LayeredWorkspacesTest extends FunctionalTestCase
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
    protected $currentUserWorkspace;

    /**
     * @var string
     */
    protected $currentGroupWorkspace;

    /**
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var Workspace
     */
    protected $liveWorkspace;

    /**
     * @var Workspace
     */
    protected $groupWorkspace;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->currentUserWorkspace = uniqid('user-', true);
        $this->currentGroupWorkspace = uniqid('group-', true);

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

        $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        if ($this->liveWorkspace === null) {
            $this->liveWorkspace = new Workspace('live');
            $this->workspaceRepository->add($this->liveWorkspace);
            $this->groupWorkspace = new Workspace($this->currentGroupWorkspace, $this->liveWorkspace);
            $this->workspaceRepository->add($this->groupWorkspace);
            $this->workspaceRepository->add(new Workspace($this->currentUserWorkspace, $this->groupWorkspace));
            $this->persistenceManager->persistAll();
        }

        $personalContext = $this->contextFactory->create(['workspaceName' => $this->currentUserWorkspace]);

        // Make sure the Workspace was created.
        $this->liveWorkspace = $personalContext->getWorkspace()->getBaseWorkspace()->getBaseWorkspace();
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
    public function nodeFromLiveWorkspaceRemovedInPersonalWorkspaceExistsRemovedInGroupWorkspace()
    {
        $liveContext = $this->contextFactory->create([]);
        $liveContext->getRootNode()->createNode('foo');
        $this->persistenceManager->persistAll();

        $this->rootNode->getNode('foo')->remove();
        $this->persistenceManager->persistAll();

        $this->rootNode->getContext()->getWorkspace()->publish($this->groupWorkspace);
        $this->persistenceManager->persistAll();

        $groupContextWithRemovedContent = $this->contextFactory->create(['workspaceName' => $this->currentGroupWorkspace, 'removedContentShown' => true]);

        $fooNodeInGroupWorkspace = $groupContextWithRemovedContent->getRootNode()->getNode('foo');

        self::assertInstanceOf(NodeInterface::class, $fooNodeInGroupWorkspace);
        self::assertSame($this->currentGroupWorkspace, $fooNodeInGroupWorkspace->getNodeData()->getWorkspace()->getName());
        self::assertTrue($fooNodeInGroupWorkspace->isRemoved());
    }

    /**
     * @test
     */
    public function nodeFromLiveWorkspaceChangedInGroupWorkspaceAndRemovedInPersonalWorkspaceExistsRemovedInGroupWorkspace()
    {
        $liveContext = $this->contextFactory->create([]);
        $liveContext->getRootNode()->createNode('foo');
        $this->persistenceManager->persistAll();

        $groupContext = $this->contextFactory->create(['workspaceName' => $this->currentGroupWorkspace]);
        $groupContext->getRootNode()->getNode('foo')->setProperty('someProperty', 'someValue');
        $this->persistenceManager->persistAll();

        $this->rootNode->getNode('foo')->remove();
        $this->persistenceManager->persistAll();

        $this->rootNode->getContext()->getWorkspace()->publish($this->groupWorkspace);
        $this->persistenceManager->persistAll();

        $groupContextWithRemovedContent = $this->contextFactory->create([
            'workspaceName' => $this->currentGroupWorkspace,
            'removedContentShown' => true
        ]);

        $fooNodeInGroupWorkspace = $groupContextWithRemovedContent->getRootNode()->getNode('foo');

        self::assertInstanceOf(NodeInterface::class, $fooNodeInGroupWorkspace);
        self::assertSame($this->currentGroupWorkspace, $fooNodeInGroupWorkspace->getNodeData()->getWorkspace()->getName());
        self::assertTrue($fooNodeInGroupWorkspace->isRemoved());
    }

    /**
     * @test
     */
    public function nodeFromLiveWorkspaceMovedInUserWorkspaceIsInCorrectPlaceAfterPublish()
    {
        $liveContext = $this->contextFactory->create([]);
        $liveContext->getRootNode()->createNode('foo')->createNode('bar')->createNode('baz');
        $this->persistenceManager->persistAll();

        $this->rootNode->getNode('foo/bar/baz')->moveInto($this->rootNode->getNode('foo'));
        $this->persistenceManager->persistAll();

        $this->rootNode->getContext()->getWorkspace()->publish($this->groupWorkspace);
        $this->persistenceManager->persistAll();

        $groupContext = $this->contextFactory->create(['workspaceName' => $this->currentGroupWorkspace]);

        $movedBazNode = $groupContext->getRootNode()->getNode('foo')->getNode('baz');
        self::assertInstanceOf(NodeInterface::class, $movedBazNode);

        $oldBazNode = $groupContext->getRootNode()->getNode('foo/bar/baz');
        self::assertNull($oldBazNode);
    }

    /**
     * @test
     */
    public function nodeFromLiveWorkspaceMovedInUserWorkspaceRetainsShadowNodeInGroupWorkspace()
    {
        $liveContext = $this->contextFactory->create([]);
        $liveContext->getRootNode()->createNode('foo')->createNode('bar')->createNode('baz');
        $this->persistenceManager->persistAll();

        $this->rootNode->getNode('foo/bar/baz')->moveInto($this->rootNode->getNode('foo'));

        $this->rootNode->getContext()->getWorkspace()->publish($this->groupWorkspace);
        $this->persistenceManager->persistAll();

        $groupContext = $this->contextFactory->create(['workspaceName' => $this->currentGroupWorkspace]);

        $movedBazNode = $groupContext->getRootNode()->getNode('foo')->getNode('baz');
        self::assertInstanceOf(NodeInterface::class, $movedBazNode);

        $shadowNode = $this->nodeDataRepository->findShadowNodeByPath('/foo/bar/baz', $this->groupWorkspace, $groupContext->getDimensions());
        self::assertInstanceOf(NodeData::class, $shadowNode);
        self::assertNotNull($shadowNode->getMovedTo());
        self::assertTrue($shadowNode->isRemoved());
    }
}
