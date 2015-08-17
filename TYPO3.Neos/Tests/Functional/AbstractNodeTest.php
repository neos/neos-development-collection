<?php
namespace TYPO3\Neos\Tests\Functional;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Base test case for nodes
 */
abstract class AbstractNodeTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var string the Nodes fixture
	 */
	protected $fixtureFileName = 'Fixtures/NodeStructure.xml';

	/**
	 * @var string the context path of the node to load initially
	 */
	protected $nodeContextPath = '/sites/example/home';

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $node;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	public function setUp() {
		parent::setUp();
		$this->markSkippedIfNodeTypesPackageIsNotInstalled();
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$contentContext = $this->contextFactory->create(array('workspaceName' => 'live'));
		$siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
		$siteImportService->importFromFile(__DIR__ . '/' . $this->fixtureFileName, $contentContext);
		$this->persistenceManager->persistAll();

		if ($this->nodeContextPath !== NULL) {
			$this->node = $this->getNodeWithContextPath($this->nodeContextPath);
		}
	}

	/**
	 * Retrieve a node through the property mapper
	 *
	 * @param $contextPath
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected function getNodeWithContextPath($contextPath) {
		/* @var $propertyMapper \TYPO3\Flow\Property\PropertyMapper */
		$propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');
		$node = $propertyMapper->convert($contextPath, 'TYPO3\TYPO3CR\Domain\Model\Node');
		$this->assertFalse($propertyMapper->getMessages()->hasErrors(), 'There were errors converting ' . $contextPath);
		return $node;
	}

	public function tearDown() {
		parent::tearDown();

		$this->inject($this->contextFactory, 'contextInstances', array());
		$this->inject($this->objectManager->get('TYPO3\Media\TypeConverter\AssetInterfaceConverter'), 'resourcesAlreadyConvertedToAssets', array());
	}

	protected function markSkippedIfNodeTypesPackageIsNotInstalled() {
		$packageManager = $this->objectManager->get('TYPO3\Flow\Package\PackageManagerInterface');
		if (!$packageManager->isPackageActive('TYPO3.Neos.NodeTypes')) {
			$this->markTestSkipped('This test needs the TYPO3.Neos.NodeTypes package.');
		}
	}
}
