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
