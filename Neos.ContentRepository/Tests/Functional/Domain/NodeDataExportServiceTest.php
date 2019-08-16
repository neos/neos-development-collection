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
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeExportService;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Functional test case for node data export.
 */
class NodeDataExportServiceTest extends FunctionalTestCase
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
     * @var Context $context
     */
    protected $context;

    /**
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpRootNodeAndRepository();
    }

    /**
     * @test
     */
    public function aSingleNodeExportedWithNodeDataExportCanBeImportedWithNodeDataImport()
    {
        $originalNode = $this->rootNode->createNode('foo', $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:ImportExport'));
        $originalNode->setProperty('description', 'Some node with a property');
        $originalNode->setProperty('someDate', new \DateTime());
        $this->persistenceManager->persistAll();

        $exportService = new NodeExportService();
        $xml = $exportService->export('/')->outputMemory();

        $this->nodeDataRepository->removeAll();
        $this->workspaceRepository->removeAll();
        $this->saveNodesAndTearDownRootNodeAndRepository();
        $this->setUpRootNodeAndRepository();

        $importService = new NodeImportService();
        $reader = new \XMLReader();
        $reader->XML($xml);
        $importService->import($reader, '/');

        $importedNode = $this->rootNode->getNode('foo');

        self::assertNotNull($importedNode, 'Expected node not found');
        self::assertSame($originalNode->getIdentifier(), $importedNode->getIdentifier());
        self::assertSame($originalNode->getProperty('description'), $importedNode->getProperty('description'));
        self::assertEqualsWithDelta($originalNode->getProperty('someDate'), $importedNode->getProperty('someDate'), 1, 'The "someDate" property had a different value after import');
    }

    /**
     * @return void
     */
    protected function setUpRootNodeAndRepository()
    {
        $this->contextFactory = $this->objectManager->get(ContextFactory::class);
        $this->context = $this->contextFactory->create(['workspaceName' => 'live']);
        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        $this->workspaceRepository->add(new Workspace('live'));
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->rootNode = $this->context->getNode('/');
        $this->persistenceManager->persistAll();
    }

    /**
     * @return void
     */
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
}
