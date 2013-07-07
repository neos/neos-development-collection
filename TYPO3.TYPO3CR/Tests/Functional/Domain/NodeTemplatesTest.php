<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * Functional test case which covers all NodeTemplate related behavior of
 * the content repository as long as they reside in the live workspace.
 */
class NodeTemplatesTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextInterface
	 */
	protected $context;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->nodeDataRepository = new \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository();
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$this->context = $this->contextFactory->create(array('workspaceName' => 'live'));
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->inject($this->contextFactory, 'contextInstances', array());
	}

	/**
	 * @test
	 */
	public function nodeTemplateConverterCanConvertArray() {
		$nodeTemplate = $this->generateBasicNodeTemplate();
		$this->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\NodeTemplate', $nodeTemplate);
		$this->assertEquals('Neos rules!', $nodeTemplate->getProperty('test1'));
	}

	/**
	 * @test
	 */
	public function newNodeCanBeCreatedFromNodeTemplate() {
		$nodeTemplate = $this->generateBasicNodeTemplate();

		$rootNode = $this->context->getNode('/');
		$node = $rootNode->createNodeFromTemplate($nodeTemplate, 'just-a-node');
		$this->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\NodeInterface', $node);
	}

	/**
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeTemplate
	 */
	protected function generateBasicNodeTemplate() {
		$source = array(
			'__nodeType' => 'TYPO3.TYPO3CR:TestingNodeType',
			'test1' => 'Neos rules!'
		);

		$typeConverter = new \TYPO3\TYPO3CR\TypeConverter\NodeTemplateConverter();
		return $typeConverter->convertFrom($source, 'TYPO3\TYPO3CR\Domain\Model\NodeTemplate');
	}
}

?>