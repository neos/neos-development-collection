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
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService;
use TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeImportService;

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
     * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->setUpRootNodeAndRepository();
    }

    /**
     * @test
     */
    public function aSingleNodeExportedWithNodeDataExportCanBeImportedWithNodeDataImport()
    {
        $originalNode = $this->rootNode->createNode('foo', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:ImportExport'));
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

        $this->assertNotNull($importedNode, 'Expected node not found');
        $this->assertSame($originalNode->getIdentifier(), $importedNode->getIdentifier());
        $this->assertSame($originalNode->getProperty('description'), $importedNode->getProperty('description'));
        $this->assertEquals($originalNode->getProperty('someDate'), $importedNode->getProperty('someDate'));
    }

    /**
     * @return void
     */
    protected function setUpRootNodeAndRepository()
    {
        $this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactory');
        $this->context = $this->contextFactory->create(array('workspaceName' => 'live'));
        $this->nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
        $this->workspaceRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository');
        $this->workspaceRepository->add(new Workspace('live'));
        $this->nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
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
        /** @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory $nodeFactory */
        $nodeFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Factory\NodeFactory');
        $nodeFactory->reset();
        $this->contextFactory->reset();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->nodeDataRepository = null;
        $this->rootNode = null;
    }
}
