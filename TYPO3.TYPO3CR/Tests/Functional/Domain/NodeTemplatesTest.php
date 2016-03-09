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

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\TypeConverter\NodeTemplateConverter;

/**
 * Functional test case which covers all NodeTemplate related behavior of
 * the content repository as long as they reside in the live workspace.
 */
class NodeTemplatesTest extends FunctionalTestCase
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
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var Workspace
     */
    protected $liveWorkspace;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->liveWorkspace = new Workspace('live');
        $this->workspaceRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository');
        $this->workspaceRepository->add($this->liveWorkspace);
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
    public function nodeTemplateConverterCanConvertArray()
    {
        $nodeTemplate = $this->generateBasicNodeTemplate();
        $this->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\NodeTemplate', $nodeTemplate);
        $this->assertEquals('Neos rules!', $nodeTemplate->getProperty('test1'));
    }

    /**
     * @test
     */
    public function newNodeCanBeCreatedFromNodeTemplate()
    {
        $nodeTemplate = $this->generateBasicNodeTemplate();

        $rootNode = $this->context->getNode('/');
        $node = $rootNode->createNodeFromTemplate($nodeTemplate, 'just-a-node');
        $this->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\NodeInterface', $node);
    }

    /**
     * @test
     */
    public function createNodeFromTemplateUsesWorkspacesOfContext()
    {
        $nodeTemplate = $this->generateBasicNodeTemplate();

        $userWorkspace = new Workspace('user1', $this->liveWorkspace);
        $this->workspaceRepository->add($userWorkspace);

        $this->context = $this->contextFactory->create(array('workspaceName' => 'user1'));

        $rootNode = $this->context->getNode('/');
        $node = $rootNode->createNodeFromTemplate($nodeTemplate, 'just-a-node');

        $workspace = $node->getWorkspace();
        $this->assertEquals('user1', $workspace->getName(), 'Node should be created in workspace of context');
    }

    /**
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeTemplate
     */
    protected function generateBasicNodeTemplate()
    {
        $source = array(
            '__nodeType' => 'TYPO3.TYPO3CR.Testing:NodeType',
            'test1' => 'Neos rules!'
        );

        $typeConverter = new NodeTemplateConverter();
        return $typeConverter->convertFrom($source, 'TYPO3\TYPO3CR\Domain\Model\NodeTemplate');
    }
}
