<?php
namespace TYPO3\Neos\Tests\Functional\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Service\SiteExportService;
use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * Tests for the SiteImportService & SiteExportService
 */
class SiteImportExportServiceTest extends FunctionalTestCase {

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
	protected $fixtureFileName = 'Fixtures/Sites.xml';

	/**
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @var Site
	 */
	protected $importedSite;

	/**
	 * @var SiteImportService
	 */
	protected $siteImportService;

	/**
	 * @var SiteExportService
	 */
	protected $siteExportService;

	public function setUp() {
		parent::setUp();
		$this->markSkippedIfNodeTypesPackageIsNotInstalled();
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$contentContext = $this->contextFactory->create(array('workspaceName' => 'live'));

		$this->siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');

		$this->siteExportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteExportService');

		$this->importedSite = $this->siteImportService->importFromFile(__DIR__ . '/' . $this->fixtureFileName, $contentContext);
		$this->persistenceManager->persistAll();
	}

	public function tearDown() {
		parent::tearDown();

		$this->inject($this->contextFactory, 'contextInstances', array());
	}

	/**
	 * @test
	 */
	public function exportingAPreviouslyImportedSiteLeadsToTheSameStructure() {
		$expectedResult = file_get_contents(__DIR__ . '/Fixtures/Sites.xml');
		$actualResult = $this->siteExportService->export(array($this->importedSite), TRUE);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @return void
	 */
	protected function markSkippedIfNodeTypesPackageIsNotInstalled() {
		$packageManager = $this->objectManager->get('TYPO3\Flow\Package\PackageManagerInterface');
		if (!$packageManager->isPackageActive('TYPO3.Neos.NodeTypes')) {
			$this->markTestSkipped('This test needs the TYPO3.Neos.NodeTypes package.');
		}
	}
}
