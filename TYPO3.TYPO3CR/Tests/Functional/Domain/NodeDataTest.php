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
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

/**
 * Functional test case.
 */
class NodeDataTest extends FunctionalTestCase
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
        $workspaceRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository');
        $workspaceRepository->add(new Workspace('live'));
        $this->persistenceManager->persistAll();
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
        $identifier = Algorithms::generateUUID();
        $template = new NodeTemplate();
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
        $identifier = Algorithms::generateUUID();
        $template = new NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $newEntity = new Fixtures\RelatedEntity();
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
        $identifier = Algorithms::generateUUID();
        $template = new NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $newEntity = new Fixtures\RelatedEntity();
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
        $identifier = Algorithms::generateUUID();
        $template = new NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $newEntity = new Fixtures\RelatedEntity();
        $newEntity->setFavoritePlace('Reykjavik');
        $anotherNewEntity = new Fixtures\RelatedEntity();
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
}
