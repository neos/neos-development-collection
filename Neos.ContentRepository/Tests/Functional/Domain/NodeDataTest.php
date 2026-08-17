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
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Tests\Functional\Domain\Fixtures\RelatedEntity;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Utility\Algorithms;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeDimension;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

/**
 * Functional test case.
 */
class NodeDataTest extends FunctionalTestCase
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        $workspaceRepository->add(new Workspace('live'));
        $this->persistenceManager->persistAll();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $this->context = $this->contextFactory->create(['workspaceName' => 'live']);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', []);
    }

    #[Test]
    public function createNodeFromTemplateUsesIdentifierFromTemplate()
    {
        $identifier = Algorithms::generateUUID();
        $template = new NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $rootNode = $this->context->getRootNode();
        $newNode = $rootNode->createNodeFromTemplate($template);

        self::assertSame($identifier, $newNode->getIdentifier());
    }

    #[Test]
    public function nodeWithRelatedEntityWillTakeCareOfAddingToPersistence()
    {
        $identifier = Algorithms::generateUUID();
        $template = new NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $newEntity = new RelatedEntity();
        $newEntity->setFavoritePlace('Reykjavik');
        $template->setProperty('entity', $newEntity);

        $rootNode = $this->context->getRootNode();

        $newNode = $rootNode->createNodeFromTemplate($template);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        $newLiveContext = $this->contextFactory->create(['workspaceName' => 'live']);
        $newNodeAgain = $newLiveContext->getNode('/new-node');

        self::assertEquals($newNode->getIdentifier(), $newNodeAgain->getIdentifier());
        self::assertEquals('Reykjavik', $newNodeAgain->getProperty('entity')->getFavoritePlace());
    }

    #[Test]
    public function nodeWithRelatedEntityWillTakeCareOfUpdatingInPersistence()
    {
        $identifier = Algorithms::generateUUID();
        $template = new NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $newEntity = new RelatedEntity();
        $newEntity->setFavoritePlace('Reykjavik');
        $template->setProperty('entity', $newEntity);

        $rootNode = $this->context->getRootNode();

        $newNode = $rootNode->createNodeFromTemplate($template);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        $newLiveContext = $this->contextFactory->create(['workspaceName' => 'live']);
        $newNodeAgain = $newLiveContext->getNode('/new-node');
        $entity = $newNodeAgain->getProperty('entity');
        self::assertEquals('Reykjavik', $entity->getFavoritePlace());
        $entity->setFavoritePlace('Iceland');
        $newNodeAgain->setProperty('entity', $entity);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        $newLiveContext = $this->contextFactory->create(['workspaceName' => 'live']);
        $newNodeAgain = $newLiveContext->getNode('/new-node');
        $entity = $newNodeAgain->getProperty('entity');

        self::assertEquals('Iceland', $entity->getFavoritePlace());
    }


    #[Test]
    public function nodeWithRelatedEntitiesWillTakeCareOfAddingToPersistence()
    {
        $identifier = Algorithms::generateUUID();
        $template = new NodeTemplate();
        $template->setName('new-node');
        $template->setIdentifier($identifier);

        $newEntity = new RelatedEntity();
        $newEntity->setFavoritePlace('Reykjavik');
        $anotherNewEntity = new RelatedEntity();
        $anotherNewEntity->setFavoritePlace('Japan');
        $template->setProperty('entity', [$newEntity, $anotherNewEntity]);

        $rootNode = $this->context->getRootNode();

        $newNode = $rootNode->createNodeFromTemplate($template);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        $newLiveContext = $this->contextFactory->create(['workspaceName' => 'live']);
        $newNodeAgain = $newLiveContext->getNode('/new-node');

        $entityArray = $newNodeAgain->getProperty('entity');

        self::assertCount(2, $entityArray);
        self::assertEquals('Japan', $entityArray[1]->getFavoritePlace());
    }

    #[Test]
    public function inContextWithEmptyDimensionsNodeVariantsWithoutDimensionsArePrioritized()
    {
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/../Fixtures/NodesWithAndWithoutDimensions.xml', $this->context);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        $nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        // The identifier comes from the Fixture.
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('78f5c720-e8df-2573-1fc1-f7ce5b338485', $context->getWorkspace(true), []);

        self::assertEmpty($resultingNodeData->getDimensions());
    }

    #[Test]
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
        /** @var NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        self::assertCount(1, $resultingNodeData->getDimensions());
        $values = $resultingNodeData->getDimensionValues();
        self::assertEquals('en_US', $values['language'][0]);
        $nodeDimension = new NodeDimension($resultingNodeData, 'language', 'lv');
        $resultingNodeData->setDimensions([$nodeDimension]);

        $nodeDataRepository->update($resultingNodeData);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        // The identifier comes from the Fixture.
        /** @var NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        self::assertCount(1, $resultingNodeData->getDimensions());
        $values = $resultingNodeData->getDimensionValues();
        self::assertEquals('lv', $values['language'][0]);
    }

    #[Test]
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
        /** @var NodeData $resultingNodeData */
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
        /** @var NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        self::assertCount(2, $resultingNodeData->getDimensions());
        $values = $resultingNodeData->getDimensionValues();
        self::assertEquals('en_US', $values['language'][0]);
        self::assertEquals('lv', $values['language'][1]);
    }

    #[Test]
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
        /** @var NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        self::assertNotEmpty($resultingNodeData->getDimensions());
        $resultingNodeData->setDimensions([]);
        $nodeDataRepository->update($resultingNodeData);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->inject($this->contextFactory, 'contextInstances', []);

        // The context is not important here, just a quick way to get a (live) workspace
        $context = $this->contextFactory->create();
        // The identifier comes from the Fixture.
        /** @var NodeData $resultingNodeData */
        $resultingNodeData = $nodeDataRepository->findOneByIdentifier('9fa376af-a1b8-83ac-bedc-9ad83c8598bc', $context->getWorkspace(true), []);
        self::assertEmpty($resultingNodeData->getDimensions());
    }
}
