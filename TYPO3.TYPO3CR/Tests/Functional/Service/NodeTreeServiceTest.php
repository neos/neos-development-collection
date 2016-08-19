<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Service;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Service\NodeTreeService;

/**
 * The node tree service
 * @Flow\Scope("singleton")
 */
class NodeTreeServiceTest extends FunctionalTestCase
{
    const TRANSLATION_CONTEXT_FALLBACK = 'fallback';
    const TRANSLATION_CONTEXT_TRANSLATED = 'translated';

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;


    /**
     * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactory
     */
    protected $contextFactory;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository
     */
    protected $contentDimensionRepository;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var NodeTreeService
     */
    protected $nodeTreeService;


    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->nodeDataRepository = new \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository();
        $this->contextFactory = $this->objectManager->get(\TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface::class);
        $this->contentDimensionRepository = $this->objectManager->get(\TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository::class);
        $this->workspaceRepository = $this->objectManager->get(\TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository::class);
        $this->nodeFactory = $this->objectManager->get(\TYPO3\TYPO3CR\Domain\Factory\NodeFactory::class);
        $this->nodeTreeService = $this->objectManager->get(NodeTreeService::class);

        $liveWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('live');
        $firstLevelNestedWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('firstLevelNested', $liveWorkspace);
        $secondLevelNestedWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('secondLevelNested', $firstLevelNestedWorkspace);
        $this->workspaceRepository->add($liveWorkspace);
        $this->workspaceRepository->add($firstLevelNestedWorkspace);
        $this->workspaceRepository->add($secondLevelNestedWorkspace);

        $this->contentDimensionRepository->setDimensionsConfiguration([
            'language' => [
                'default' => 'en_US',
                'defaultPreset' => 'en_US',
                'presets' => [
                    'en_US' => [
                        'label' => 'en_US',
                        'values' => ['en_US']
                    ],
                    'en_UK' => [
                        'label' => 'en_UK',
                        'values' => ['en_UK', 'en_US']
                    ]
                ]
            ]
        ]);
    }


    /**
     * @test
     */
    public function traverseTreeVisitsAllNodesInFallbackDimensionValuesAndLiveWorkspace()
    {
        $rootNode = $this->getRootNode();

        $childNode = $rootNode->createNode('child-node');
        $grandChildNode = $childNode->createNode('grandchild-node');
        $yetAnotherChildNode = $rootNode->createNode('yet-another-child-node');

        $expectedIdentifiers = [
            $rootNode->getContextPath(),
            $childNode->getContextPath(),
            $grandChildNode->getContextPath(),
            $yetAnotherChildNode->getContextPath()
        ];

        $this->persistAndFlush();

        $actualIdentifiers = [];
        $this->nodeTreeService->traverseTree($rootNode, function (NodeInterface $node) use (&$actualIdentifiers) {
            $actualIdentifiers[] = $node->getContextPath();
        });

        $this->assertSame($expectedIdentifiers, $actualIdentifiers);
    }

    /**
     * @test
     */
    public function traverseTreeVisitsNodesAddedInFallbackDimensionValuesAndFirstLevelNestedWorkspace()
    {
        $modificationRootNode = $this->getRootNode(self::TRANSLATION_CONTEXT_FALLBACK, 'firstLevelNested');

        $addedChildNode = $modificationRootNode->createNode('added-child-node');
        $addedChildNodeContextPath = $addedChildNode->getContextPath();

        $this->persistAndFlush();

        $actualIdentifiers = [];
        $this->nodeTreeService->traverseTree($modificationRootNode, function (NodeInterface $node) use (&$actualIdentifiers) {
            $actualIdentifiers[] = $node->getContextPath();
        });

        $this->assertContains($addedChildNodeContextPath, $actualIdentifiers);
    }

    /**
     * @test
     */
    public function traverseTreeDoesNotVisitNodesRemovedInFallbackDimensionValuesAndFirstLevelNestedWorkspace()
    {
        $rootNode = $this->getRootNode();
        $modificationRootNode = $this->getRootNode(self::TRANSLATION_CONTEXT_FALLBACK, 'firstLevelNested');

        $childNode = $rootNode->createNode('doomed-child-node');
        $doomedChildNode = $modificationRootNode->getContext()->adoptNode($childNode);
        $doomedChildNodeContextPath = $doomedChildNode->getContextPath();

        $doomedChildNode->remove();

        $this->persistAndFlush();

        $actualIdentifiers = [];
        $this->nodeTreeService->traverseTree($modificationRootNode, function (NodeInterface $node) use (&$actualIdentifiers) {
            $actualIdentifiers[] = $node->getContextPath();
        });

        $this->assertNotContains($doomedChildNodeContextPath, $actualIdentifiers);
    }

    /**
     * @test
     */
    public function traverseTreeVisitsNodesAddedInTranslationDimensionValuesAndLiveWorkspace()
    {
        $modificationRootNode = $this->getRootNode(self::TRANSLATION_CONTEXT_TRANSLATED);

        $addedChildNode = $modificationRootNode->createNode('added-child-node');
        $addedChildNodeContextPath = $addedChildNode->getContextPath();

        $this->persistAndFlush();

        $actualIdentifiers = [];
        $this->nodeTreeService->traverseTree($modificationRootNode, function (NodeInterface $node) use (&$actualIdentifiers) {
            $actualIdentifiers[] = $node->getContextPath();
        });

        $this->assertContains($addedChildNodeContextPath, $actualIdentifiers);
    }

    /**
     * @test
     */
    public function traverseTreeVisitsFallbackOfNodesRemovedInTranslationDimensionValuesAndLiveWorkspace()
    {
        $rootNode = $this->getRootNode();
        $modificationRootNode = $this->getRootNode(self::TRANSLATION_CONTEXT_TRANSLATED);

        $childNode = $rootNode->createNode('added-child-node');
        $addedChildNodeInModificationContext = $modificationRootNode->getContext()->adoptNode($childNode);
        $addedChildNodeContextPath = $addedChildNodeInModificationContext->getContextPath();

        $addedChildNodeInModificationContext->remove();

        $this->persistAndFlush();

        $actualIdentifiers = [];
        $this->nodeTreeService->traverseTree($modificationRootNode, function (NodeInterface $node) use (&$actualIdentifiers) {
            $actualIdentifiers[] = $node->getContextPath();
        });

        $this->assertContains($addedChildNodeContextPath, $actualIdentifiers);
    }

    /**
     * @test
     */
    public function traverseTreeVisitsNodesAddedInTranslationDimensionValuesAndFirstLevelNestedWorkspace()
    {
        $modificationRootNode = $this->getRootNode(self::TRANSLATION_CONTEXT_TRANSLATED, 'firstLevelNested');

        $addedChildNode = $modificationRootNode->createNode('added-child-node');
        $addedChildNodeContextPath = $addedChildNode->getContextPath();

        $this->persistAndFlush();

        $actualIdentifiers = [];
        $this->nodeTreeService->traverseTree($modificationRootNode, function (NodeInterface $node) use (&$actualIdentifiers) {
            $actualIdentifiers[] = $node->getContextPath();
        });

        $this->assertContains($addedChildNodeContextPath, $actualIdentifiers);
    }

    /**
     * @test
     */
    public function traverseTreeDoesNotVisitNodesRemovedInTranslationDimensionValuesAndFirstLevelNestedWorkspace()
    {
        $rootNode = $this->getRootNode();
        $modificationRootNode = $this->getRootNode(self::TRANSLATION_CONTEXT_TRANSLATED, 'firstLevelNested');

        $addedChildNode = $rootNode->createNode('added-child-node');
        $doomedChildNodeInModificationContext = $modificationRootNode->getContext()->adoptNode($addedChildNode);
        $doomedChildNodeContextPath = $doomedChildNodeInModificationContext->getContextPath();

        $doomedChildNodeInModificationContext->remove();

        $this->persistAndFlush();

        $actualIdentifiers = [];
        $this->nodeTreeService->traverseTree($modificationRootNode, function (NodeInterface $node) use (&$actualIdentifiers) {
            $actualIdentifiers[] = $node->getContextPath();
        });

        $this->assertNotContains($doomedChildNodeContextPath, $actualIdentifiers);
    }

    /**
     * @return void
     */
    protected function persistAndFlush()
    {
        $this->persistenceManager->persistAll();
        $this->nodeFactory->reset();
    }

    /**
     * @param $translationContext
     * @param $workspaceName
     * @return NodeInterface
     */
    protected function getRootNode($translationContext = self::TRANSLATION_CONTEXT_FALLBACK, $workspaceName = 'live')
    {
        $context = $this->contextFactory->create([
            'dimensions' => [
                'language' => $translationContext === self::TRANSLATION_CONTEXT_TRANSLATED ? ['en_UK', 'en_US'] : ['en_US']
            ],
            'targetDimensions' => [
                'language' => $translationContext === self::TRANSLATION_CONTEXT_TRANSLATED ? 'en_UK' : 'en_US'
            ],
            'workspaceName' => $workspaceName
        ]);

        return $context->getRootNode();
    }


    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', []);
        $this->contentDimensionRepository->setDimensionsConfiguration([]);
    }
}
