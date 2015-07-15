<?php
namespace TYPO3\TYPO3CR\Tests\Functional\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\TypeConverter\NodeConverter;

/**
 * Functional test case which tests the node converter
 *
 */
class NodeConverterTest extends FunctionalTestCase {

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
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\Node
	 */
	protected $rootNode;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @var string
	 */
	protected $currentTestWorkspaceName;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @var Workspace
	 */
	protected $liveWorkspace;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$contentDimensionRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
		$contentDimensionRepository->setDimensionsConfiguration(array(
			'language' => array(
				'default' => 'mul_ZZ'
			)
		));
		$this->currentTestWorkspaceName = uniqid('user-');
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactory');

		$this->personalContext = $this->contextFactory->create(array('workspaceName' => $this->currentTestWorkspaceName));
		$this->liveContext = $this->contextFactory->create(array('workspaceName' => 'live'));
		$this->rootNodeInLiveWorkspace = $this->liveContext->getNode('/');
		$this->persistenceManager->persistAll();
		$this->rootNodeInPersonalWorkspace = $this->personalContext->getNode('/');
	}

	public function tearDown() {
		$contentDimensionRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
		$contentDimensionRepository->setDimensionsConfiguration(array());
		parent::tearDown();
	}

	/**
	 * Set up the following node structure:
	 *
	 * /headline (TYPO3.TYPO3CR.Testing:Headline)
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
	protected function setupNodeWithShadowNodeInPersonalWorkspace() {
		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$headlineNode = $this->rootNodeInLiveWorkspace->createNode('headline', $nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Headline'));
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
	public function nodeFromLiveWorkspaceCanBeRetrievedAgainUsingNodeConverter() {
		$this->setupNodeWithShadowNodeInPersonalWorkspace();

		$headlineNode = $this->convert('/headline');
		$this->assertSame('Hello World', $headlineNode->getProperty('title'));
	}

	/**
	 * @test
	 */
	public function nodeFromPersonalWorkspaceCanBeRetrievedAgainUsingNodeConverter() {
		$this->setupNodeWithShadowNodeInPersonalWorkspace();

		$headlineNode = $this->convert('/headline@' . $this->currentTestWorkspaceName);
		$this->assertSame('Hello World', $headlineNode->getProperty('title'));

		$this->assertSame('Brave new world', $headlineNode->getProperty('subtitle'));
	}

	/**
	 * @test
	 */
	public function nodeFromGermanDimensionIsFetchedCorrectly() {
		$this->setupNodeWithShadowNodeInPersonalWorkspace();

		$headlineNode = $this->convert('/headline@' . $this->currentTestWorkspaceName . ';language=de_DE');
		$this->assertSame('Hallo Welt', $headlineNode->getProperty('title'));
	}

	/**
	 * @test
	 */
	public function nodePropertiesAreSetWhenConverterIsCalledWithInputArray() {
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
	 * @expectedException \TYPO3\Flow\Property\Exception\TypeConverterException
	 */
	public function settingUnknownNodePropertiesThrowsException() {
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
	public function unknownNodePropertiesAreSkippedIfTypeConverterIsConfiguredLikeThis() {
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
	protected function convert($nodePath, PropertyMappingConfiguration $propertyMappingConfiguration = NULL) {
		$nodeConverter = new NodeConverter();
		$result = $nodeConverter->convertFrom($nodePath, NULL, array(), $propertyMappingConfiguration);
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
	public function flushNodeChanges() {
		$nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
		$nodeDataRepository->flushNodeRegistry();
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
		$this->contextFactory->reset();
	}
}
