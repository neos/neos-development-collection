<?php
namespace Neos\Neos\Tests\Functional\FlowQueryOperations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Eel\FlowQueryOperations\SortOperation;
use Neos\ContentRepository\Domain\Model\Workspace;

/**
 * SortOperation test
 */
class SortOperationTest extends FunctionalTestCase
{
    /**
     * @var \Neos\ContentRepository\Domain\Service\Context
     */
    protected $context;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var \Neos\ContentRepository\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $workspaceRepository = $this->objectManager->get(\Neos\ContentRepository\Domain\Repository\WorkspaceRepository::class);
        $workspaceRepository->add(new Workspace('live'));
        $this->persistenceManager->persistAll();
        $this->contextFactory = $this->objectManager->get(\Neos\ContentRepository\Domain\Service\ContextFactoryInterface::class);
        $this->context = $this->contextFactory->create(array('workspaceName' => 'live'));


        $siteImportService = $this->objectManager->get(\Neos\Neos\Domain\Service\SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/Fixtures/SortableNodes.xml', $this->context);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', array());

        // The context is not important here, just a quick way to get a (live) workspace
        //        $context = $this->contextFactory->create();
        $this->nodeDataRepository = $this->objectManager->get(\Neos\ContentRepository\Domain\Repository\NodeDataRepository::class);
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
     * @expectedException \Neos\Eel\FlowQuery\FlowQueryException
     */
    public function callWithoutArgumentsCausesException()
    {
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery([]);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, []);
    }

    /**
     * @test
     * @expectedException \Neos\Eel\FlowQuery\FlowQueryException
     */
    public function invalidSortDirectionCausesException()
    {
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery([]);
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
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery($nodesToSort);
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
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery($nodesToSort);
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
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery($nodesToSort);
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
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery($nodesToSort);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, ['_lastPublicationDateTime', 'DESC']);

        $this->assertEquals($correctOrder, $flowQuery->getContext());
    }
}
