<?php
namespace Neos\ContentRepository\Tests\Functional\TypeConverter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Error;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\TypeConverter\NodeConverter;

/**
 * Functional test case which tests the node converter
 *
 */
class NodeConverterTest extends FunctionalTestCase
{
    protected $personalContext;

    protected $liveContext;

    /**
     * @var NodeInterface
     */
    protected $rootNodeInLiveWorkspace;

    /**
     * @var NodeInterface
     */
    protected $rootNodeInPersonalWorkspace;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var Node
     */
    protected $rootNode;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var string
     */
    protected $currentTestWorkspaceName;

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
        $contentDimensionRepository = $this->objectManager->get(ContentDimensionRepository::class);
        $contentDimensionRepository->setDimensionsConfiguration(array(
            'language' => array(
                'default' => 'mul_ZZ'
            )
        ));
        $this->currentTestWorkspaceName = uniqid('user-');
        $this->contextFactory = $this->objectManager->get(ContextFactory::class);

        if ($this->liveWorkspace === null) {
            $this->liveWorkspace = new Workspace('live');
            $this->workspaceRepository = $this->objectManager->get(WorkspaceRepository::class);
            $this->workspaceRepository->add($this->liveWorkspace);
        }

        $this->workspaceRepository->add(new Workspace($this->currentTestWorkspaceName, $this->liveWorkspace));

        $this->personalContext = $this->contextFactory->create(array('workspaceName' => $this->currentTestWorkspaceName));
        $this->liveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
        $this->rootNodeInLiveWorkspace = $this->liveContext->getNode('/');
        $this->persistenceManager->persistAll();
        $this->rootNodeInPersonalWorkspace = $this->personalContext->getNode('/');
    }

    public function tearDown()
    {
        $contentDimensionRepository = $this->objectManager->get(ContentDimensionRepository::class);
        $contentDimensionRepository->setDimensionsConfiguration(array());
        parent::tearDown();
    }

    /**
     * Set up the following node structure:
     *
     * /headline (Neos.ContentRepository.Testing:Headline)
     *   - live workspace
     *     - title: Hello World
     *   - personal workspace
     *     - title: Hello World
     *     - subtitle: Brave new world
     * /headline with language=de_DE
     *   - personal workspace
     *     - title: Hallo Welt
     * @return void
     */
    protected function setupNodeWithShadowNodeInPersonalWorkspace()
    {
        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $headlineNode = $this->rootNodeInLiveWorkspace->createNode('headline', $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Headline'));
        $headlineNode->setProperty('title', 'Hello World');
        $headlineNodeInPersonalWorkspace = $this->rootNodeInPersonalWorkspace->getNode('headline');
        $headlineNodeInPersonalWorkspace->setProperty('subtitle', 'Brave new world');

        $germanContext = $this->contextFactory->create(array('workspaceName' => $this->currentTestWorkspaceName, 'dimensions' => array('language' => array('de_DE', 'mul_ZZ'))));
        $headlineInGerman = $germanContext->getNode('/headline');
        $headlineInGerman->setProperty('title', 'Hallo Welt');

        $this->flushNodeChanges();
    }

    /**
     * @test
     */
    public function nodeFromLiveWorkspaceCanBeRetrievedAgainUsingNodeConverter()
    {
        $this->setupNodeWithShadowNodeInPersonalWorkspace();

        $headlineNode = $this->convert('/headline');
        $this->assertSame('Hello World', $headlineNode->getProperty('title'));
    }

    /**
     * @test
     */
    public function nodeFromPersonalWorkspaceCanBeRetrievedAgainUsingNodeConverter()
    {
        $this->setupNodeWithShadowNodeInPersonalWorkspace();

        $headlineNode = $this->convert('/headline@' . $this->currentTestWorkspaceName);
        $this->assertSame('Hello World', $headlineNode->getProperty('title'));

        $this->assertSame('Brave new world', $headlineNode->getProperty('subtitle'));
    }

    /**
     * @test
     */
    public function nodeFromGermanDimensionIsFetchedCorrectly()
    {
        $this->setupNodeWithShadowNodeInPersonalWorkspace();

        $headlineNode = $this->convert('/headline@' . $this->currentTestWorkspaceName . ';language=de_DE');
        $this->assertSame('Hallo Welt', $headlineNode->getProperty('title'));
    }

    /**
     * @test
     */
    public function nodePropertiesAreSetWhenConverterIsCalledWithInputArray()
    {
        $this->setupNodeWithShadowNodeInPersonalWorkspace();
        $input = array(
            '__contextNodePath' => '/headline@' . $this->currentTestWorkspaceName,
            'title' => 'New title'
        );

        $headlineNode = $this->convert($input);
        $this->assertSame('New title', $headlineNode->getProperty('title'));
        $this->assertSame('Brave new world', $headlineNode->getProperty('subtitle'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Property\Exception\TypeConverterException
     */
    public function settingUnknownNodePropertiesThrowsException()
    {
        $this->setupNodeWithShadowNodeInPersonalWorkspace();
        $input = array(
            '__contextNodePath' => '/headline@' . $this->currentTestWorkspaceName,
            'title' => 'New title',
            'non-existing-input' => 'test'
        );
        $this->convert($input);
    }

    /**
     * @test
     */
    public function unknownNodePropertiesAreSkippedIfTypeConverterIsConfiguredLikeThis()
    {
        $this->setupNodeWithShadowNodeInPersonalWorkspace();
        $input = array(
            '__contextNodePath' => '/headline@' . $this->currentTestWorkspaceName,
            'title' => 'New title',
            'non-existing-input' => 'test'
        );
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->skipUnknownProperties();
        $headlineNode = $this->convert($input, $propertyMappingConfiguration);
        $this->assertSame('New title', $headlineNode->getProperty('title'));
        $this->assertSame('Brave new world', $headlineNode->getProperty('subtitle'));
        $this->assertFalse($headlineNode->hasProperty('non-existing-input'));
    }

    /**
     * Helper which calls the NodeConverter; with some error-handling built in
     *
     * @param $nodePath
     * @return NodeInterface
     */
    protected function convert($nodePath, PropertyMappingConfiguration $propertyMappingConfiguration = null)
    {
        $nodeConverter = new NodeConverter();
        $result = $nodeConverter->convertFrom($nodePath, null, array(), $propertyMappingConfiguration);
        if ($result instanceof Error) {
            $this->fail('Failed with error: ' . $result->getMessage());
        }
        return $result;
    }

    /**
     * Flush the node changes and reset the persistence manager and node data registry
     *
     * @return void
     */
    public function flushNodeChanges()
    {
        $nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        $nodeDataRepository->flushNodeRegistry();
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->contextFactory->reset();
    }
}
