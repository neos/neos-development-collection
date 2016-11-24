<?php
namespace TYPO3\Neos\Tests\Functional\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Tests\FunctionalTestCase;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Service\SiteExportService;
use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * Tests for the SiteImportService & SiteExportService
 */
class SiteImportExportServiceTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

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

    public function setUp()
    {
        parent::setUp();
        $this->markSkippedIfNodeTypesPackageIsNotInstalled();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contentContext = $this->contextFactory->create(array('workspaceName' => 'live'));

        $this->siteImportService = $this->objectManager->get(SiteImportService::class);

        $this->siteExportService = $this->objectManager->get(SiteExportService::class);

        $this->importedSite = $this->siteImportService->importFromFile(__DIR__ . '/' . $this->fixtureFileName, $contentContext);
        $this->persistenceManager->persistAll();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->inject($this->contextFactory, 'contextInstances', array());
    }

    /**
     * @test
     */
    public function exportingAPreviouslyImportedSiteLeadsToTheSameStructure()
    {
        $expectedResult = file_get_contents(__DIR__ . '/Fixtures/Sites.xml');
        $actualResult = $this->siteExportService->export(array($this->importedSite), true);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return void
     */
    protected function markSkippedIfNodeTypesPackageIsNotInstalled()
    {
        $packageManager = $this->objectManager->get(PackageManagerInterface::class);
        if (!$packageManager->isPackageActive('TYPO3.Neos.NodeTypes')) {
            $this->markTestSkipped('This test needs the TYPO3.Neos.NodeTypes package.');
        }
    }
}
