<?php
namespace Neos\Neos\Tests\Functional\Fusion\Cache;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Fusion\Cache\ContentCacheFlusher;
use Neos\Neos\Fusion\Helper\CachingHelper;
use Neos\Utility\ObjectAccess;

/**
 * Tests the CachingHelper
 */
class ContentCacheFlusherTest extends FunctionalTestCase
{
    protected static $testablePersistenceEnabled = true;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ContentCacheFlusher
     */
    protected $contentCacheFlusher;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $this->contentCacheFlusher = $this->objectManager->get(ContentCacheFlusher::class);

        $this->context = $this->contextFactory->create(['workspaceName' => 'live']);
        $siteImportService = $this->objectManager->get(\Neos\Neos\Domain\Service\SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/Fixtures/CacheableNodes.xml', $this->context);

        // Assume an empty state for $contentCacheFlusher - this is needed as importing nodes will register
        // changes to the ContentCacheFlusher
        $this->inject($this->contentCacheFlusher, 'workspacesToFlush', []);
        $this->inject($this->contentCacheFlusher, 'tagsToFlush', []);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * @test
     */
    public function flushingANodeWillResolveAllWorkspacesToFlush()
    {
        // Add more workspaces
        $workspaceFirstLevel = new Workspace('first-level');
        $workspaceSecondLevel = new Workspace('second-level');
        $workspaceAlsoOnSecondLevel = new Workspace('also-second-level');
        $workspaceThirdLevel = new Workspace('third-level');

        // And build up a chain
        $liveWorkspace = $this->workspaceRepository->findByIdentifier('live');
        $workspaceThirdLevel->setBaseWorkspace($workspaceSecondLevel);
        $workspaceSecondLevel->setBaseWorkspace($workspaceFirstLevel);
        $workspaceAlsoOnSecondLevel->setBaseWorkspace($workspaceFirstLevel);
        $workspaceFirstLevel->setBaseWorkspace($liveWorkspace);

        $this->workspaceRepository->add($workspaceFirstLevel);
        $this->workspaceRepository->add($workspaceSecondLevel);
        $this->workspaceRepository->add($workspaceAlsoOnSecondLevel);
        $this->workspaceRepository->add($workspaceThirdLevel);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        // Make sure that we do have multiple workspaces set up in our database
        self::assertEquals(5, $this->workspaceRepository->countAll());

        // Create/Fetch a node in workspace "first-level"
        $fistLevelContext = $this->contextFactory->create(['workspaceName' => $workspaceFirstLevel->getName()]);
        $nodeInFirstLevelWorkspace = $fistLevelContext->getRootNode();

        // When the node is flushed we expect three workspaces to be flushed
        $this->contentCacheFlusher->registerNodeChange($nodeInFirstLevelWorkspace);

        $workspacesToFlush = ObjectAccess::getProperty($this->contentCacheFlusher, 'workspacesToFlush', true);
        $workspaceChain = $workspacesToFlush['first-level'];

        self::assertArrayNotHasKey('live', $workspaceChain);
        self::assertArrayHasKey('first-level', $workspaceChain);
        self::assertArrayHasKey('second-level', $workspaceChain);
        self::assertArrayHasKey('also-second-level', $workspaceChain);
        self::assertArrayHasKey('third-level', $workspaceChain);
    }

    /**
     * @test
     */
    public function flushingANodeWithAnAdditionalTargetWorkspaceWillAlsoResolveThatWorkspace()
    {
        // Add more workspaces
        $workspaceFirstLevel = new Workspace('first-level');
        $workspaceSecondLevel = new Workspace('second-level');
        $workspaceAlsoOnSecondLevel = new Workspace('also-second-level');
        $workspaceThirdLevel = new Workspace('third-level');
        $workspaceAlsoFirstLevel = new Workspace('also-first-level');

        // And build up a chain
        $liveWorkspace = $this->workspaceRepository->findByIdentifier('live');
        $workspaceThirdLevel->setBaseWorkspace($workspaceSecondLevel);
        $workspaceSecondLevel->setBaseWorkspace($workspaceFirstLevel);
        $workspaceAlsoOnSecondLevel->setBaseWorkspace($workspaceFirstLevel);
        $workspaceFirstLevel->setBaseWorkspace($liveWorkspace);
        $workspaceAlsoFirstLevel->setBaseWorkspace($liveWorkspace);

        $this->workspaceRepository->add($workspaceFirstLevel);
        $this->workspaceRepository->add($workspaceSecondLevel);
        $this->workspaceRepository->add($workspaceAlsoOnSecondLevel);
        $this->workspaceRepository->add($workspaceThirdLevel);
        $this->workspaceRepository->add($workspaceAlsoFirstLevel);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        // Make sure that we do have multiple workspaces set up in our database
        self::assertEquals(6, $this->workspaceRepository->countAll());

        // Create/Fetch a node in workspace "first-level"
        $fistLevelContext = $this->contextFactory->create(['workspaceName' => $workspaceFirstLevel->getName()]);
        $nodeInFirstLevelWorkspace = $fistLevelContext->getRootNode();

        // When the node is flushed we expect three workspaces to be flushed
        $this->contentCacheFlusher->registerNodeChange($nodeInFirstLevelWorkspace, $workspaceAlsoFirstLevel);

        $workspacesToFlush = ObjectAccess::getProperty($this->contentCacheFlusher, 'workspacesToFlush', true);

        self::assertArrayHasKey('also-first-level', $workspacesToFlush);
        self::assertArrayHasKey('first-level', $workspacesToFlush);
    }

    /**
     * @test
     */
    public function aNodeChangeWillRegisterNodeIdentifierTagsForAllWorkspaces()
    {
        $workspaceFirstLevel = new Workspace('first-level');

        $liveWorkspace = $this->workspaceRepository->findByIdentifier('live');
        $workspaceFirstLevel->setBaseWorkspace($liveWorkspace);
        $this->workspaceRepository->add($workspaceFirstLevel);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $nodeIdentifier = 'c381f64d-4269-429a-9c21-6d846115addd';
        $nodeToFlush = $this->context->getNodeByIdentifier($nodeIdentifier);

        $this->contentCacheFlusher->registerNodeChange($nodeToFlush);

        $tagsToFlush = ObjectAccess::getProperty($this->contentCacheFlusher, 'tagsToFlush', true);

        $cachingHelper = new CachingHelper();

        $workspacesToTest = [];
        $workspacesToTest[$liveWorkspace->getName()] = $cachingHelper->renderWorkspaceTagForContextNode($liveWorkspace->getName());
        $workspacesToTest[$workspaceFirstLevel->getName()] = $cachingHelper->renderWorkspaceTagForContextNode($workspaceFirstLevel->getName());

        foreach ($workspacesToTest as $name => $workspaceHash) {
            self::assertArrayHasKey('Node_'.$workspaceHash.'_'.$nodeIdentifier, $tagsToFlush, 'on workspace ' . $name);
            self::assertArrayHasKey('DescendantOf_'.$workspaceHash.'_'.$nodeIdentifier, $tagsToFlush, 'on workspace ' . $name);
        }
    }

    /**
     * @test
     */
    public function aNodeChangeWillRegisterNodeTypeTagsForAllWorkspaces()
    {
        $workspaceFirstLevel = new Workspace('first-level');

        $liveWorkspace = $this->workspaceRepository->findByIdentifier('live');
        $workspaceFirstLevel->setBaseWorkspace($liveWorkspace);
        $this->workspaceRepository->add($workspaceFirstLevel);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $nodeIdentifier = 'c381f64d-4269-429a-9c21-6d846115addd';
        $nodeToFlush = $this->context->getNodeByIdentifier($nodeIdentifier);

        $this->contentCacheFlusher->registerNodeChange($nodeToFlush);

        $tagsToFlush = ObjectAccess::getProperty($this->contentCacheFlusher, 'tagsToFlush', true);

        $cachingHelper = new CachingHelper();

        $workspacesToTest = [];
        $workspacesToTest[$liveWorkspace->getName()] = $cachingHelper->renderWorkspaceTagForContextNode($liveWorkspace->getName());
        $workspacesToTest[$workspaceFirstLevel->getName()] = $cachingHelper->renderWorkspaceTagForContextNode($workspaceFirstLevel->getName());

        // Check for tags that respect the workspace hash
        foreach ($workspacesToTest as $name => $workspaceHash) {
            self::assertArrayHasKey('NodeType_'.$workspaceHash.'_Neos.Neos:Content', $tagsToFlush, 'on workspace ' . $name);
            self::assertArrayHasKey('NodeType_'.$workspaceHash.'_Neos.Neos:Node', $tagsToFlush, 'on workspace ' . $name);
            self::assertArrayHasKey('NodeType_'.$workspaceHash.'_Acme.Demo:Text', $tagsToFlush, 'on workspace ' . $name);
        }
    }

    /**
     * @test
     */
    public function aNodeChangeWillRegisterAllDescendantOfTagsForAllWorkspaces()
    {
        $workspaceFirstLevel = new Workspace('first-level');

        $liveWorkspace = $this->workspaceRepository->findByIdentifier('live');
        $workspaceFirstLevel->setBaseWorkspace($liveWorkspace);
        $this->workspaceRepository->add($workspaceFirstLevel);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $nodeIdentifier = 'c381f64d-4269-429a-9c21-6d846115addd';
        $nodeToFlush = $this->context->getNodeByIdentifier($nodeIdentifier);

        $this->contentCacheFlusher->registerNodeChange($nodeToFlush);

        $tagsToFlush = ObjectAccess::getProperty($this->contentCacheFlusher, 'tagsToFlush', true);

        $cachingHelper = new CachingHelper();

        $workspacesToTest = [];
        $workspacesToTest[$liveWorkspace->getName()] = $cachingHelper->renderWorkspaceTagForContextNode($liveWorkspace->getName());
        $workspacesToTest[$workspaceFirstLevel->getName()] = $cachingHelper->renderWorkspaceTagForContextNode($workspaceFirstLevel->getName());

        foreach ($workspacesToTest as $name => $workspaceHash) {
            self::assertArrayHasKey('DescendantOf_'.$workspaceHash.'_c381f64d-4269-429a-9c21-6d846115addd', $tagsToFlush, 'on workspace ' . $name);
            self::assertArrayHasKey('DescendantOf_'.$workspaceHash.'_c381f64d-4269-429a-9c21-6d846115adde', $tagsToFlush, 'on workspace ' . $name);
            self::assertArrayHasKey('DescendantOf_'.$workspaceHash.'_c381f64d-4269-429a-9c21-6d846115addf', $tagsToFlush, 'on workspace ' . $name);
        }
    }

    public function tearDown(): void
    {
        $this->contextFactory->reset();
        parent::tearDown();
    }
}
