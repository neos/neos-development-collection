<?php

namespace Neos\Neos\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\Site;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the "Site" domain model
 *
 */
class SiteTest extends UnitTestCase
{
    #[Test]
    public function aNameCanBeSetAndRetrievedFromTheSite()
    {
        $site = new Site('');
        $site->setName('My cool website');
        self::assertSame('My cool website', $site->getName());
    }

    #[Test]
    public function theDefaultStateOfASiteIsOffline()
    {
        $site = new Site('');
        self::assertSame(Site::STATE_OFFLINE, $site->getState());
    }

    #[Test]
    public function theStateCanBeSetAndRetrieved()
    {
        $site = new Site('');
        $site->setState(Site::STATE_ONLINE);
        self::assertSame(Site::STATE_ONLINE, $site->getState());
    }

    #[Test]
    public function theSiteResourcesPackageKeyCanBeSetAndRetrieved()
    {
        $site = new Site('');
        $site->setSiteResourcesPackageKey('Foo');
        self::assertSame('Foo', $site->getSiteResourcesPackageKey());
    }

    public static function getConfigurationFailingDataProvider(): iterable
    {
        yield 'no matching nor default site config' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => [], 'sitePresetsConfiguration' => [], 'expectedExceptionMessage' => 'Missing configuration for "Neos.Neos.sites.siteNodeName" or fallback "Neos.Neos.sites.*"'];
        yield 'referring non-string preset' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => ['siteNodeName' => ['preset' => false]], 'sitePresetsConfiguration' => [], 'expectedExceptionMessage' => 'Invalid "preset" configuration for "Neos.Neos.sites.siteNodeName". Expected string, got: bool'];
        yield 'referring non-existing preset' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => ['siteNodeName' => ['preset' => 'nonExistingPreset']], 'sitePresetsConfiguration' => [], 'expectedExceptionMessage' => 'Site settings "Neos.Neos.sites.siteNodeName" refer to a preset "nonExistingPreset"'];
        yield 'missing content repository identifier' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => ['siteNodeName' => []], 'sitePresetsConfiguration' => [], 'expectedExceptionMessage' => 'There is no content repository identifier configured in Sites configuration in Settings.yaml: Neos.Neos.sites.*.contentRepository'];
        yield 'missing content dimension resolver factory' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => ['siteNodeName' => ['contentRepository' => 'default']], 'sitePresetsConfiguration' => [], 'expectedExceptionMessage' => 'No Dimension Resolver Factory configured at Neos.Neos.sites.*.contentDimensions.resolver.factoryClassName'];
    }

    #[DataProvider('getConfigurationFailingDataProvider')]
    #[Test]
    public function getConfigurationFailingTests(string $nodeTypeName, array $sitesConfiguration, array $sitePresetsConfiguration, string $expectedExceptionMessage): void
    {
        $site = new Site($nodeTypeName);
        ObjectAccess::setProperty($site, 'sitesConfiguration', $sitesConfiguration, true);
        ObjectAccess::setProperty($site, 'sitePresetsConfiguration', $sitePresetsConfiguration, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $site->getConfiguration();
    }

    public static function getConfigurationSucceedingDataProvider(): iterable
    {
        yield 'minimal configuration' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => ['siteNodeName' => ['contentRepository' => 'default', 'contentDimensions' => ['resolver' => ['factoryClassName' => 'Foo']]]], 'sitePresetsConfiguration' => [], 'expectedConfiguration' => ['contentRepositoryId' => 'default', 'contentDimensionResolverFactoryClassName' => 'Foo', 'contentDimensionResolverOptions' => [], 'defaultDimensionSpacePoint' => [], 'uriPathSuffix' => '']];
        yield 'full configuration' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => ['siteNodeName' => ['contentRepository' => 'custom_repo', 'contentDimensions' => ['resolver' => ['factoryClassName' => 'Bar', 'options' => ['some' => 'options']], 'defaultDimensionSpacePoint' => ['language' => 'de']], 'uriPathSuffix' => 'some-suffix']], 'sitePresetsConfiguration' => [], 'expectedConfiguration' => ['contentRepositoryId' => 'custom_repo', 'contentDimensionResolverFactoryClassName' => 'Bar', 'contentDimensionResolverOptions' => ['some' => 'options'], 'defaultDimensionSpacePoint' => ['language' => 'de'], 'uriPathSuffix' => 'some-suffix']];
        yield 'full configuration from fallback' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => ['*' => ['contentRepository' => 'custom_repo', 'contentDimensions' => ['resolver' => ['factoryClassName' => 'Bar', 'options' => ['some' => 'options']], 'defaultDimensionSpacePoint' => ['language' => 'de']], 'uriPathSuffix' => 'some-suffix']], 'sitePresetsConfiguration' => [], 'expectedConfiguration' => ['contentRepositoryId' => 'custom_repo', 'contentDimensionResolverFactoryClassName' => 'Bar', 'contentDimensionResolverOptions' => ['some' => 'options'], 'defaultDimensionSpacePoint' => ['language' => 'de'], 'uriPathSuffix' => 'some-suffix']];
        yield 'full configuration merged with preset' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => ['siteNodeName' => ['preset' => 'somePreset', 'contentDimensions' => ['defaultDimensionSpacePoint' => ['country' => 'DE']], 'uriPathSuffix' => 'some-overridden-suffix']], 'sitePresetsConfiguration' => ['somePreset' => ['contentRepository' => 'custom_repo', 'contentDimensions' => ['resolver' => ['factoryClassName' => 'Bar', 'options' => ['some' => 'options']], 'defaultDimensionSpacePoint' => ['language' => 'de']], 'uriPathSuffix' => 'some-default-suffix']], 'expectedConfiguration' => ['contentRepositoryId' => 'custom_repo', 'contentDimensionResolverFactoryClassName' => 'Bar', 'contentDimensionResolverOptions' => ['some' => 'options'], 'defaultDimensionSpacePoint' => ['language' => 'de', 'country' => 'DE'], 'uriPathSuffix' => 'some-overridden-suffix']];
        yield 'full configuration from fallback merged with preset' => ['nodeTypeName' => 'siteNodeName', 'sitesConfiguration' => ['*' => ['preset' => 'somePreset', 'contentDimensions' => ['defaultDimensionSpacePoint' => ['country' => 'DE']], 'uriPathSuffix' => 'some-overridden-suffix']], 'sitePresetsConfiguration' => ['somePreset' => ['contentRepository' => 'custom_repo', 'contentDimensions' => ['resolver' => ['factoryClassName' => 'Bar', 'options' => ['some' => 'options']], 'defaultDimensionSpacePoint' => ['language' => 'de']], 'uriPathSuffix' => 'some-default-suffix']], 'expectedConfiguration' => ['contentRepositoryId' => 'custom_repo', 'contentDimensionResolverFactoryClassName' => 'Bar', 'contentDimensionResolverOptions' => ['some' => 'options'], 'defaultDimensionSpacePoint' => ['language' => 'de', 'country' => 'DE'], 'uriPathSuffix' => 'some-overridden-suffix']];
    }

    #[DataProvider('getConfigurationSucceedingDataProvider')]
    #[Test]
    public function getConfigurationSucceedingTests(string $nodeTypeName, array $sitesConfiguration, array $sitePresetsConfiguration, array $expectedConfiguration): void
    {
        $site = new Site($nodeTypeName);
        ObjectAccess::setProperty($site, 'sitesConfiguration', $sitesConfiguration, true);
        ObjectAccess::setProperty($site, 'sitePresetsConfiguration', $sitePresetsConfiguration, true);

        $configuration = $site->getConfiguration();
        self::assertSame($expectedConfiguration, json_decode(json_encode($configuration), true));
    }
}
