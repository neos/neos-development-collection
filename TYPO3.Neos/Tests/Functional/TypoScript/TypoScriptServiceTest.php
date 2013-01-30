<?php
namespace TYPO3\Neos\Tests\Functional\TypoScript;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Model\Site;

/**
 * Functional test case which tests the TypoScript Service
 */
class TypoScriptServiceTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 */
	protected $homeNode;

	/**
	 * Set up node structure
	 */
	public function setUp() {
		parent::setUp();

		$site = new Site('example');
		$site->setSiteResourcesPackageKey('TYPO3.Neos');

		$context = new ContentContext('live');
		$context->setCurrentSite($site);

		$nodeRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeRepository');
		ObjectAccess::setProperty($nodeRepository, 'context', $context, TRUE);

		$siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
		$siteImportService->importSitesFromFile(__DIR__ . '/Fixtures/NodeStructure.xml');
		$this->persistenceManager->persistAll();

		$propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');
		$this->homeNode = $propertyMapper->convert('/sites/example/home', 'TYPO3\TYPO3CR\Domain\Model\Node');
		$this->assertFalse($propertyMapper->getMessages()->hasErrors());
	}

	/**
	 * @test
	 */
	public function overridingFromTypoScriptInFilesystemFollowingNodePathsWorks() {
		$typoScriptService = $this->objectManager->get('TYPO3\Neos\Domain\Service\TypoScriptService');
		ObjectAccess::setProperty($typoScriptService, 'typoScriptsPathPattern', __DIR__ . '/Fixtures/ResourcesFixture/TypoScripts', TRUE);

		$objectTree = $typoScriptService->getMergedTypoScriptObjectTree($this->homeNode, $this->homeNode->getNode('about-us/history'));

		$this->assertEquals('Root', $objectTree['text1']['value']);
		$this->assertEquals('AboutUs', $objectTree['text2']['value']);
		$this->assertEquals('History', $objectTree['text3']['value']);
		$this->assertEquals('Additional', $objectTree['text4']['value']);
	}


}
?>