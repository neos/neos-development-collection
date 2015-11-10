<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain\Repository;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Image;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Tests\Functional\Domain\Fixtures\TestObjectForSerialization;

/**
 * Functional test case.
 */
class NodeDataRepositoryTest extends FunctionalTestCase
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
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
        $this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
        $this->context = $this->contextFactory->create(array('workspaceName' => 'live'));
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
    public function findNodesByRelatedEntitiesFindsExistingNodeWithMatchingEntityProperty()
    {
        $rootNode = $this->context->getRootNode();
        $newNode = $rootNode->createNode('test', $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithEntities'));

        $testImage = new Image();
        $this->persistenceManager->add($testImage);

        $newNode->setProperty('image', $testImage);

        $this->persistenceManager->persistAll();

        $relationMap = array(
            'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Image' => array($this->persistenceManager->getIdentifierByObject($testImage))
        );

        $result = $this->nodeDataRepository->findNodesByRelatedEntities($relationMap);

        $this->assertCount(1, $result);
    }
}
