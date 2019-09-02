<?php
namespace Neos\Neos\Tests\Functional\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Service\SiteExportService;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

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

    public function setUp(): void
    {
        parent::setUp();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contentContext = $this->contextFactory->create(['workspaceName' => 'live']);

        $this->siteImportService = $this->objectManager->get(SiteImportService::class);

        $this->siteExportService = $this->objectManager->get(SiteExportService::class);

        $this->importedSite = $this->siteImportService->importFromFile(__DIR__ . '/' . $this->fixtureFileName, $contentContext);
        $this->persistenceManager->persistAll();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->inject($this->contextFactory, 'contextInstances', []);
    }

    /**
     * @test
     */
    public function exportingAPreviouslyImportedSiteLeadsToTheSameStructure()
    {
        $expectedResult = file_get_contents(__DIR__ . '/Fixtures/Sites.xml');
        $actualResult = $this->siteExportService->export([$this->importedSite], true);
        self::assertEquals($expectedResult, $actualResult);
    }
}
