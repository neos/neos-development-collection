<?php
namespace TYPO3\Neos\Tests\Functional\FlowQueryOperations;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Neos\Eel\FlowQueryOperations\SortOperation;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

/**
 * SortOperation test
 */
class SortOperationTest extends FunctionalTestCase
{
    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\Context
     */
    protected $context;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $workspaceRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository');
        $workspaceRepository->add(new Workspace('live'));
        $this->persistenceManager->persistAll();
        $this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
        $this->context = $this->contextFactory->create(array('workspaceName' => 'live'));


        $siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
        $siteImportService->importFromFile(__DIR__ . '/Fixtures/SortableNodes.xml', $this->context);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', array());

        // The context is not important here, just a quick way to get a (live) workspace
        //        $context = $this->contextFactory->create();
        $this->nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', array());
    }

    /**
     * @test
     */
    public function callWithoutArgumentsCausesException()
    {
        $this->expectException('\TYPO3\Eel\FlowQuery\FlowQueryException');
        $flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery([]);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, []);
    }

    /**
     * @test
     */
    public function invalidSortDirectionCausesException()
    {
        $this->expectException('\TYPO3\Eel\FlowQuery\FlowQueryException');
        $flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery([]);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, ['title', 'FOO']);
    }

    /**
     * @test
     */
    public function sortByStringAscending()
    {
        $nodesToSort = [
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addd', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115adde', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addf', $this->context->getWorkspace(true), array())
        ];
        $correctOrder = [
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addf', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addd', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115adde', $this->context->getWorkspace(true), array())
        ];
        $flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery($nodesToSort);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, ['title', 'ASC']);

        $this->assertEquals($correctOrder, $flowQuery->getContext());
    }

    /**
     * @test
     */
    public function sortByStringDescending()
    {
        $nodesToSort = [
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addd', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115adde', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addf', $this->context->getWorkspace(true), array())
        ];
        $correctOrder = [
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115adde', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addd', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addf', $this->context->getWorkspace(true), array())
        ];
        $flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery($nodesToSort);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, ['title', 'DESC']);

        $this->assertEquals($correctOrder, $flowQuery->getContext());
    }

    /**
     * @test
     */
    public function sortByDateTimeAscending()
    {
        $nodesToSort = [
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addd', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115adde', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addf', $this->context->getWorkspace(true), array())
        ];
        $correctOrder = [
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115adde', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addf', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addd', $this->context->getWorkspace(true), array())
        ];
        $flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery($nodesToSort);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, ['_lastPublicationDateTime', 'ASC']);

        $this->assertEquals($correctOrder, $flowQuery->getContext());
    }

    /**
     * @test
     */
    public function sortByDateTimeDescending()
    {
        $nodesToSort = [
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addd', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115adde', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addf', $this->context->getWorkspace(true), array())
        ];
        $correctOrder = [
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addd', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115addf', $this->context->getWorkspace(true), array()),
            $this->nodeDataRepository->findOneByIdentifier('c381f64d-4269-429a-9c21-6d846115adde', $this->context->getWorkspace(true), array())
        ];
        $flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery($nodesToSort);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, ['_lastPublicationDateTime', 'DESC']);

        $this->assertEquals($correctOrder, $flowQuery->getContext());
    }
}
