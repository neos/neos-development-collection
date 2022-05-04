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
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;

/**
 * Functional test case which covers https://github.com/neos/neos-development-collection/issues/3265
 */
class CopyNodeAcrossDimensionsBug3265Test extends FunctionalTestCase
{
    /**
     * @var Context
     */
    protected $liveContextDe;

    /**
     * @var Context
     */
    protected $liveContextEn;

    /**
     * @var Context
     */
    protected $userWorkspaceContextDe;

    /**
     * @var Context
     */
    protected $userWorkspaceContextEn;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var Workspace
     */
    protected $liveWorkspace;

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
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->persistAndResetStateCompletely();
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
    public function testForBug3265()
    {
        // FIXTURE SETUP
        // node1 in DE and EN (live)
        // node2 in DE and EN (live)
        $this->liveContextDe->getRootNode()->createNode('node1', null, 'node1-identifier');
        $this->liveContextDe->getRootNode()->createNode('node2', null, 'node2-identifier');
        $this->liveContextEn->getRootNode()->createNode('node1', null, 'node1-identifier');
        $this->liveContextEn->getRootNode()->createNode('node2', null, 'node2-identifier');
        $this->persistAndResetStateCompletely();

        // REPRODUCING THE PROBLEM
        // 1) we remove the node1 in DE (in user workspace)
        $this->userWorkspaceContextDe->getRootNode()->getNode('node1')->remove();
        $this->persistAndResetStateCompletely();

        // 2) we copy the node1 from EN to DE after node2 (in user workspace)
        $enNode1 = $this->userWorkspaceContextEn->getRootNode()->getNode('node1');
        $deNode2 = $this->userWorkspaceContextDe->getRootNode()->getNode('node2');
        $this->assertNotNull($enNode1, 'enNode1 must not be null');
        $this->assertNotNull($deNode2, 'deNode2 must not be null');
        $enNode1->copyAfter($deNode2, 'node-1-pasted');
        $this->persistAndResetStateCompletely();

        // EXPECTATION / ASSERTION
        // the copied node must appear underneath the root
        $childNodes = $this->userWorkspaceContextDe->getRootNode()->getChildNodes();
        $childNodePaths = [];
        foreach ($childNodes as $node) {
            $childNodePaths[] = $node->getPath();
        }
        $expected = ['/node2', '/node-1-pasted'];
        $this->assertEquals($expected, $childNodePaths, 'We copied node-1-pasted, but it does not appear');
    }

    protected function persistAndResetStateCompletely()
    {
        if ($this->nodeDataRepository !== null) {
            $this->nodeDataRepository->flushNodeRegistry();
        }
        /** @var NodeFactory $nodeFactory */
        $nodeFactory = $this->objectManager->get(NodeFactory::class);
        $nodeFactory->reset();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $this->contextFactory->reset();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->nodeDataRepository = null;
        $this->rootNode = null;

        $this->nodeDataRepository = new NodeDataRepository();

        if ($this->liveWorkspace === null) {
            $this->liveWorkspace = new Workspace('live');
            $this->objectManager->get(WorkspaceRepository::class);
            $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
            $this->workspaceRepository->add($this->liveWorkspace);
            $this->workspaceRepository->add(new Workspace('user-admin', $this->liveWorkspace));
            $this->persistenceManager->persistAll();
        }


        $this->liveContextDe = $this->contextFactory->create([
            'workspaceName' => 'live',
            'dimensions' => [
                'language' => ['de']
            ],
            'targetDimensions' => [
                'language' => 'de'
            ]
        ]);
        $this->liveContextEn = $this->contextFactory->create([
            'workspaceName' => 'live',
            'dimensions' => [
                'language' => ['en']
            ],
            'targetDimensions' => [
                'language' => 'en'
            ]
        ]);
        $this->userWorkspaceContextDe = $this->contextFactory->create([
            'workspaceName' => 'user-admin',
            'dimensions' => [
                'language' => ['de']
            ],
            'targetDimensions' => [
                'language' => 'de'
            ]
        ]);
        $this->userWorkspaceContextEn = $this->contextFactory->create([
            'workspaceName' => 'user-admin',
            'dimensions' => [
                'language' => ['en']
            ],
            'targetDimensions' => [
                'language' => 'en'
            ]
        ]);
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->contentDimensionRepository = $this->objectManager->get(ContentDimensionRepository::class);

        $this->contentDimensionRepository->setDimensionsConfiguration([
            'language' => [
                'default' => 'en',
                'presets' => [
                    'enPreset' => [
                        'values' => ['en']
                    ],
                    'dePreset' => [
                        'values' => ['de']
                    ]
                ]
            ]
        ]);
    }
}
