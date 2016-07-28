<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\TYPO3CR\Domain\Model\NodeDimension;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * Functional test case.
 */
class NodeDataTest extends \TYPO3\Flow\Tests\FunctionalTestCase
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
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
        $this->context = $this->contextFactory->create(array('workspaceName' => 'live'));
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
    public function createNodeFromTemplateUsesIdentifierFromTemplate()
    {
        $identifier = \TYPO3\Flow\Utility\Algorithms::generateUUID();
        $template = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $rootNode = $this->context->getRootNode();
        $newNode = $rootNode->createNodeFromTemplate($template);

        $this->assertSame($identifier, $newNode->getIdentifier());
    }

    /**
     * @test
     */
    public function nodeWithRelatedEntityWillTakeCareOfAddingToPersistence()
    {
        $identifier = \TYPO3\Flow\Utility\Algorithms::generateUUID();
        $template = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $newEntity = new \TYPO3\TYPO3CR\Tests\Functional\Domain\Fixtures\RelatedEntity();
        $newEntity->setFavoritePlace('Reykjavik');
        $template->setProperty('entity', $newEntity);

        $rootNode = $this->context->getRootNode();

        $newNode = $rootNode->createNodeFromTemplate($template);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', array());

        $newLiveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $newNodeAgain = $newLiveContext->getNode('/new-node');

        $this->assertEquals($newNode->getIdentifier(), $newNodeAgain->getIdentifier());
        $this->assertEquals('Reykjavik', $newNodeAgain->getProperty('entity')->getFavoritePlace());
    }

    /**
     * @test
     */
    public function nodeWithRelatedEntityWillTakeCareOfUpdatingInPersistence()
    {
        $identifier = \TYPO3\Flow\Utility\Algorithms::generateUUID();
        $template = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $newEntity = new \TYPO3\TYPO3CR\Tests\Functional\Domain\Fixtures\RelatedEntity();
        $newEntity->setFavoritePlace('Reykjavik');
        $template->setProperty('entity', $newEntity);

        $rootNode = $this->context->getRootNode();

        $newNode = $rootNode->createNodeFromTemplate($template);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', array());

        $newLiveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $newNodeAgain = $newLiveContext->getNode('/new-node');
        $entity = $newNodeAgain->getProperty('entity');
        $this->assertEquals('Reykjavik', $entity->getFavoritePlace());
        $entity->setFavoritePlace('Iceland');
        $newNodeAgain->setProperty('entity', $entity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', array());

        $newLiveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $newNodeAgain = $newLiveContext->getNode('/new-node');
        $entity = $newNodeAgain->getProperty('entity');

        $this->assertEquals('Iceland', $entity->getFavoritePlace());
    }


    /**
     * @test
     */
    public function nodeWithRelatedEntitiesWillTakeCareOfAddingToPersistence()
    {
        $identifier = \TYPO3\Flow\Utility\Algorithms::generateUUID();
        $template = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $newEntity = new \TYPO3\TYPO3CR\Tests\Functional\Domain\Fixtures\RelatedEntity();
        $newEntity->setFavoritePlace('Reykjavik');
        $anotherNewEntity = new \TYPO3\TYPO3CR\Tests\Functional\Domain\Fixtures\RelatedEntity();
        $anotherNewEntity->setFavoritePlace('Japan');
        $template->setProperty('entity', array($newEntity, $anotherNewEntity));

        $rootNode = $this->context->getRootNode();

        $newNode = $rootNode->createNodeFromTemplate($template);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', array());

        $newLiveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $newNodeAgain = $newLiveContext->getNode('/new-node');

        $entityArray = $newNodeAgain->getProperty('entity');

        $this->assertCount(2, $entityArray);
        $this->assertEquals('Japan', $entityArray[1]->getFavoritePlace());
    }

    /**
     * @test
     */
    public function inContextWithEmptyDimensionsNodeVariantsWithoutDimensionsArePrioritized()
    {
        $siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
        $siteImportService->importFromFile(__DIR__ . '/../Fixtures/NodesWithAndWithoutDimensions.xml', $this->context);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', array());

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        $nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
        // The identifier comes from the Fixture.
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('78f5c720-e8df-2573-1fc1-f7ce5b338485', $context->getWorkspace(true), array());

        $this->assertEmpty($resultingNodeData->getDimensions());
    }

    /**
     * @test
     */
    public function setDimensionsSetsDimensions()
    {
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/../Fixtures/NodeStructure.xml', $this->context);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        $nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        // The identifier comes from the Fixture.
        /** @var \TYPO3\TYPO3CR\Domain\Model\NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        $this->assertCount(1, $resultingNodeData->getDimensions());
        $values = $resultingNodeData->getDimensionValues();
        $this->assertEquals('en_US', $values['language'][0]);
        $nodeDimension = new NodeDimension($resultingNodeData, 'language', 'lv');
        $resultingNodeData->setDimensions([$nodeDimension]);

        $nodeDataRepository->update($resultingNodeData);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        // The identifier comes from the Fixture.
        /** @var \TYPO3\TYPO3CR\Domain\Model\NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        $this->assertCount(1, $resultingNodeData->getDimensions());
        $values = $resultingNodeData->getDimensionValues();
        $this->assertEquals('lv', $values['language'][0]);
    }

    /**
     * @test
     */
    public function setDimensionsKeepsExistingDimensions()
    {
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/../Fixtures/NodeStructure.xml', $this->context);
        $this->persistenceManager->persistAll();
        $this->inject($this->contextFactory, 'contextInstances', []);

        $nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        // The identifier comes from the Fixture.
        /** @var \TYPO3\TYPO3CR\Domain\Model\NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        $resultingNodeData->setDimensions([
            new NodeDimension($resultingNodeData, 'language', 'lv'),
            new NodeDimension($resultingNodeData, 'language', 'en_US')
        ]);
        $nodeDataRepository->update($resultingNodeData);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        // The identifier comes from the Fixture.
        /** @var \TYPO3\TYPO3CR\Domain\Model\NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        $this->assertCount(2, $resultingNodeData->getDimensions());
        $values = $resultingNodeData->getDimensionValues();
        $this->assertEquals('en_US', $values['language'][0]);
        $this->assertEquals('lv', $values['language'][1]);
    }

    /**
     * @test
     */
    public function setDimensionsToEMptyArrayRemovesDimensions()
    {
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/../Fixtures/NodeStructure.xml', $this->context);
        $this->persistenceManager->persistAll();
        $this->inject($this->contextFactory, 'contextInstances', []);

        $nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        // The identifier comes from the Fixture.
        /** @var \TYPO3\TYPO3CR\Domain\Model\NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        $this->assertNotEmpty($resultingNodeData->getDimensions());
        $resultingNodeData->setDimensions([]);
        $nodeDataRepository->update($resultingNodeData);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        // The identifier comes from the Fixture.
        /** @var \TYPO3\TYPO3CR\Domain\Model\NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        $this->assertEmpty($resultingNodeData->getDimensions());
    }
}
