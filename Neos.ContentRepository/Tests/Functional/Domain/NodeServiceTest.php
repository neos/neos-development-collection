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

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeService;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Functional test case which should cover all NodeService behavior.
 */
class NodeServiceTest extends FunctionalTestCase
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
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var ContentDimensionRepository
     */
    protected $contentDimensionRepository;

    /**
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var NodeService
     */
    protected $nodeService;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->nodeDataRepository = new NodeDataRepository();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $this->context = $this->contextFactory->create(['workspaceName' => 'live']);
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->contentDimensionRepository = $this->objectManager->get(ContentDimensionRepository::class);
        $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
        $this->nodeService = $this->objectManager->get(NodeService::class);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', []);
        $configuredDimensions = $this->objectManager->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.ContentRepository.contentDimensions');
        $this->contentDimensionRepository->setDimensionsConfiguration($configuredDimensions);
    }

    /**
     * @test
     */
    public function nodePathAvailableForNodeWillReturnFalseIfNodeWithGivenPathExistsAlready()
    {
        $this->workspaceRepository->add(new Workspace('live'));
        $rootNode = $this->context->getRootNode();

        $fooNode = $rootNode->createNode('foo');
        $fooNode->createNode('bar');
        $bazNode = $rootNode->createNode('baz');
        $this->persistenceManager->persistAll();

        $actualResult = $this->nodeService->nodePathAvailableForNode('/foo/bar', $bazNode);
        self::assertFalse($actualResult);
    }
}
