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
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\TypeConverter\NodeTemplateConverter;

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
    public function setUp(): void
    {
        parent::setUp();

        $this->liveWorkspace = new Workspace('live');
        $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        $this->workspaceRepository->add($this->liveWorkspace);
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

    /**
     * @test
     */
    public function nodeTemplateConverterCanConvertArray()
    {
        $nodeTemplate = $this->generateBasicNodeTemplate();
        self::assertInstanceOf(NodeTemplate::class, $nodeTemplate);
        self::assertEquals('Neos rules!', $nodeTemplate->getProperty('test1'));
    }

    /**
     * @test
     */
    public function newNodeCanBeCreatedFromNodeTemplate()
    {
        $nodeTemplate = $this->generateBasicNodeTemplate();

        $rootNode = $this->context->getNode('/');
        $node = $rootNode->createNodeFromTemplate($nodeTemplate, 'just-a-node');
        self::assertInstanceOf(NodeInterface::class, $node);
    }

    /**
     * @test
     */
    public function createNodeFromTemplateUsesWorkspacesOfContext()
    {
        $nodeTemplate = $this->generateBasicNodeTemplate();

        $userWorkspace = new Workspace('user1', $this->liveWorkspace);
        $this->workspaceRepository->add($userWorkspace);

        $this->context = $this->contextFactory->create(['workspaceName' => 'user1']);

        $rootNode = $this->context->getNode('/');
        $node = $rootNode->createNodeFromTemplate($nodeTemplate, 'just-a-node');

        $workspace = $node->getWorkspace();
        self::assertEquals('user1', $workspace->getName(), 'Node should be created in workspace of context');
    }

    /**
     * @return NodeTemplate
     */
    protected function generateBasicNodeTemplate()
    {
        $source = [
            '__nodeType' => 'Neos.ContentRepository.Testing:NodeType',
            'test1' => 'Neos rules!'
        ];

        $typeConverter = new NodeTemplateConverter();
        return $typeConverter->convertFrom($source, NodeTemplate::class);
    }
}
