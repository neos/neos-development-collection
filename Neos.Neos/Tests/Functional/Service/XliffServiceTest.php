<?php

namespace Neos\Neos\Tests\Functional\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Composer\ComposerUtility;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Xliff\Service\XliffFileProvider;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Service\XliffService;
use org\bovigo\vfs\vfsStream;

/**
 * Test case for the XliffService
 */
class XliffServiceTest extends FunctionalTestCase
{
    /**
     * @var XliffService
     */
    protected $xliffService;

    /**
     * @var XliffFileProvider
     */
    protected $fileProvider;

    /**
     * @var array
     */
    protected $packages;


    /**
     * Initialize dependencies
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->xliffService = $this->objectManager->get(XliffService::class);
        $this->fileProvider = $this->objectManager->get(XliffFileProvider::class);

        $this->packages = $this->setUpPackages();
        ComposerUtility::flushCaches();

        $mockPackageManager = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockPackageManager->expects(self::any())
            ->method('getFlowPackages')
            ->will(self::returnValue($this->packages));
        $mockPackageManager->expects(self::any())
            ->method('getPackage')
            ->with($this->logicalOr(
                $this->equalTo('Vendor.BasePackage'),
                $this->equalTo('Vendor.DependentPackage')
            ))
            ->will(self::returnCallback([$this, 'myCallback']));
        $this->inject($this->xliffService, 'packageManager', $mockPackageManager);
        $this->inject($this->fileProvider, 'packageManager', $mockPackageManager);

        $this->inject($this->xliffService, 'packagesRegisteredForAutoInclusion', [
            'Vendor.BasePackage' => ['Included'],
            'Vendor.DependentPackage' => ['Included']
        ]);

        $cacheManager = $this->objectManager->get(CacheManager::class);
        $cacheManager->getCache('Neos_Neos_XliffToJsonTranslations')->flush();
        $cacheManager->getCache('Flow_I18n_XmlModelCache')->flush();
    }

    public function myCallback($foo)
    {
        return $this->packages[$foo];
    }

    /**
     * @return array|Package[]
     */
    protected function setUpPackages()
    {
        vfsStream::setup('Packages');

        $basePackage = $this->setUpPackage('BasePackage', [
            'de/BasePackage.Included.xlf' => 'Resources/Private/Translations/de/Included.xlf',
            'de/BasePackage.NotIncluded.xlf' => 'Resources/Private/Translations/de/NotIncluded.xlf'
        ]);
        $packages[$basePackage->getPackageKey()] = $basePackage;

        $dependentPackage = $this->setUpPackage('DependentPackage', [
            'de/DependentPackage.Included.xlf' => 'Resources/Private/Translations/de/Included.xlf',
            'de/DependentPackage.NotIncluded.xlf' => 'Resources/Private/Translations/de/NotIncluded.xlf'
        ]);
        $packages[$dependentPackage->getPackageKey()] = $dependentPackage;

        return $packages;
    }

    /**
     * @param string $packageName
     * @param array $filePaths
     * @return Package
     */
    protected function setUpPackage($packageName, array $filePaths)
    {
        $vendorName = 'Vendor';
        $packagePath = 'vfs://Packages/Application/' . $vendorName . '/' . $packageName . '/';
        $composerName = strtolower($vendorName) . '/' . strtolower($packageName);
        $packageKey = $vendorName . '.' . $packageName;
        mkdir($packagePath, 0700, true);
        mkdir($packagePath . 'Resources/Private/Translations/de/', 0700, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "' . $composerName . '", "type": "neos-test"}');

        $fixtureBasePath = __DIR__ . '/Fixtures/';
        foreach ($filePaths as $fixturePath => $targetPath) {
            copy($fixtureBasePath . $fixturePath, $packagePath . $targetPath);
        }

        return new Package($packageKey, $composerName, $packagePath);
    }

    /**
     * @test
     */
    public function getCachedJsonRespectsIncludedFiles()
    {
        $translationResult = json_decode($this->xliffService->getCachedJson(new Locale('de')), true);

        self::assertArrayHasKey(
            'Included',
            $translationResult['Vendor_BasePackage']
        );
    }

    /**
     * @test
     */
    public function getCachedJsonDoesNotRespectNotIncludedFiles()
    {
        $translationResult = json_decode($this->xliffService->getCachedJson(new Locale('de')), true);

        self::assertArrayNotHasKey(
            'NotIncluded',
            $translationResult['Vendor_BasePackage']
        );
    }

    /**
     * @test
     */
    public function getCachedJsonRespectsOverride()
    {
        $translationResult = json_decode($this->xliffService->getCachedJson(new Locale('de')), true);

        self::assertSame(
            'Anders übersetzte Zeichenkette',
            $translationResult['Vendor_BasePackage']['Included']['key1']
        );
    }
}
