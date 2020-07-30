<?php
namespace Neos\Media\Tests\Unit\Domain\Model\Dto;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\Dto\AssetConstraints;

/**
 * Test case for the AssetConstraints DTO
 */
class AssetConstraintsTest extends UnitTestCase
{

    /**
     * @test
     */
    public function createCreatesInstanceWithoutConstraints(): void
    {
        $constraints = AssetConstraints::create();
        self::assertFalse($constraints->hasAssetSourceConstraint());
        self::assertFalse($constraints->hasMediaTypeConstraint());
    }

    public function fromArrayDataProvider(): array
    {
        return [
            ['input' => [], 'expectedAllowedAssetSourceIdentifiers' => [], 'expectedAllowedMediaTypes' => []],
            ['input' => ['assetSources' => ['identifier1']], 'expectedAllowedAssetSourceIdentifiers' => ['identifier1'], 'expectedAllowedMediaTypes' => []],
            ['input' => ['assetSources' => ['identifier1', 'identifier2']], 'expectedAllowedAssetSourceIdentifiers' => ['identifier1', 'identifier2'], 'expectedAllowedMediaTypes' => []],
            ['input' => ['mediaTypes' => ['image/*']], 'expectedAllowedAssetSourceIdentifiers' => [], 'expectedAllowedMediaTypes' => ['image/*']],
            ['input' => ['mediaTypes' => ['application/*', 'audio/*']], 'expectedAllowedAssetSourceIdentifiers' => [], 'expectedAllowedMediaTypes' => ['application/*', 'audio/*']],
            ['input' => ['mediaTypes' => ['application/*'], 'assetSources' => ['identifier1', 'identifier2']], 'expectedAllowedAssetSourceIdentifiers' => ['identifier1', 'identifier2'], 'expectedAllowedMediaTypes' => ['application/*']],
        ];
    }

    /**
     * @param array $input
     * @param array $expectedAllowedAssetSourceIdentifiers
     * @param array $expectedAllowedMediaTypes
     * @test
     * @dataProvider fromArrayDataProvider
     */
    public function fromArrayTests(array $input, array $expectedAllowedAssetSourceIdentifiers, array $expectedAllowedMediaTypes): void
    {
        $constraints = AssetConstraints::fromArray($input);
        self::assertSame($expectedAllowedAssetSourceIdentifiers, $constraints->getAllowedAssetSourceIdentifiers());
        self::assertSame($expectedAllowedMediaTypes, $constraints->getAllowedMediaTypes());
    }

    public function invalidFromArrayDataProvider(): array
    {
        return [
            [['unknown-constraint' => 'foo']],
            [['assetSources' => 'no-array']],
            [['assetSources' => 123]],
            [['mediaTypes' => 'no-array']],
            [['mediaTypes' => 123]],
            [['mediaTypes' => ['invalid-media-type']]],
            [['assetSources' => ['valid'], 'mediaTypes' => ['invalid']]],
            [['assetSources' => 'invalid', 'mediaTypes' => ['image/*']]],
            [['assetSources' => ['valid'], 'unknown-constraint' => 'foo']],
            [['mediaTypes' => ['image/*'], 'unknown-constraint' => 'foo']],
        ];
    }

    /**
     * @param array $input
     * @test
     * @dataProvider invalidFromArrayDataProvider
     */
    public function invalidFromArrayTests(array $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AssetConstraints::fromArray($input);
    }

    /**
     * @test
     */
    public function withAssetSourceConstraintAllowsToChangeAssetSourceIdentifiers(): void
    {
        $constraints = AssetConstraints::fromArray(['assetSources' => ['id1'], 'mediaTypes' => ['image/*']]);
        $constraints = $constraints->withAssetSourceConstraint(['id2']);
        self::assertSame(['id2'], $constraints->getAllowedAssetSourceIdentifiers());
        self::assertSame(['image/*'], $constraints->getAllowedMediaTypes());
    }

    /**
     * @test
     */
    public function withoutAssetSourceConstraintClearsAssetSourceIdentifiers(): void
    {
        $constraints = AssetConstraints::fromArray(['assetSources' => ['id1', 'id2'], 'mediaTypes' => ['image/*']]);
        $constraints = $constraints->withoutAssetSourceConstraint();
        self::assertFalse($constraints->hasAssetSourceConstraint());
        self::assertSame(['image/*'], $constraints->getAllowedMediaTypes());
    }

    /**
     * @test
     */
    public function withMediaTypeConstraintAllowsToChangeAllowedMediaTypes(): void
    {
        $constraints = AssetConstraints::fromArray(['assetSources' => ['id1']]);
        $constraints = $constraints->withMediaTypeConstraint(['image/*']);
        self::assertSame(['id1'], $constraints->getAllowedAssetSourceIdentifiers());
        self::assertSame(['image/*'], $constraints->getAllowedMediaTypes());
    }

    /**
     * @test
     */
    public function withoutAssetTypeConstraintClearsAllowedMediaTypes(): void
    {
        $constraints = AssetConstraints::fromArray(['assetSources' => ['id1'], 'mediaTypes' => ['image/*']]);
        $constraints = $constraints->withoutAssetTypeConstraint();
        self::assertSame(['id1'], $constraints->getAllowedAssetSourceIdentifiers());
        self::assertFalse($constraints->hasMediaTypeConstraint());
    }

    /**
     * @test
     */
    public function getMediaTypeAcceptAttributeReturnsAnEmptyStringIfNoAssetConstraintsAreSet(): void
    {
        $constraints = AssetConstraints::create();
        self::assertSame('', $constraints->getMediaTypeAcceptAttribute());
    }

    /**
     * @test
     */
    public function getMediaTypeAcceptAttributeReturnsACommaSeparatedListOfAllActiveMediaTypeConstraints(): void
    {
        $constraints = AssetConstraints::create()->withMediaTypeConstraint(['image/*', 'audio/wav']);
        self::assertSame('image/*,audio/wav', $constraints->getMediaTypeAcceptAttribute());
    }

    /**
     * @test
     */
    public function applyToAssetSourcesDoesNotHaveAnyEffectWhenNoAssetSourceConstraintsAreActive(): void
    {
        $constraints = AssetConstraints::create();
        $mockAssetSources = [
            $this->getMockBuilder(AssetSourceInterface::class)->getMock(),
            $this->getMockBuilder(AssetSourceInterface::class)->getMock(),
        ];
        $resultingAssetSources = $constraints->applyToAssetSources($mockAssetSources);
        self::assertSame($mockAssetSources, $resultingAssetSources);
    }

    /**
     * @test
     */
    public function applyToAssetSourcesFiltersDisallowedAssetSources(): void
    {
        $constraints = AssetConstraints::fromArray(['assetSources' => ['id1']]);

        $mockAssetSource1 = $this->getMockBuilder(AssetSourceInterface::class)->getMock();
        $mockAssetSource1->method('getIdentifier')->willReturn('id1');

        $mockAssetSource2 = $this->getMockBuilder(AssetSourceInterface::class)->getMock();
        $mockAssetSource2->method('getIdentifier')->willReturn('id2');

        $mockAssetSources = [
            $mockAssetSource1,
            $mockAssetSource2,
        ];
        $resultingAssetSources = $constraints->applyToAssetSources($mockAssetSources);
        self::assertSame([$mockAssetSource1], $resultingAssetSources);
    }


    /**
     * @test
     */
    public function applyToAssetSourcesFiltersAllAssetSourcesIfThereIsNoMatch(): void
    {
        $constraints = AssetConstraints::fromArray(['assetSources' => ['id3']]);

        $mockAssetSource1 = $this->getMockBuilder(AssetSourceInterface::class)->getMock();
        $mockAssetSource1->method('getIdentifier')->willReturn('id1');

        $mockAssetSource2 = $this->getMockBuilder(AssetSourceInterface::class)->getMock();
        $mockAssetSource2->method('getIdentifier')->willReturn('id2');

        $mockAssetSources = [
            $mockAssetSource1,
            $mockAssetSource2,
        ];
        $resultingAssetSources = $constraints->applyToAssetSources($mockAssetSources);
        self::assertSame([], $resultingAssetSources);
    }

    public function applyToAssetSourceIdentifiersDataProvider(): array
    {
        return [
            ['allowedAssetSourceIdentifiers' => [], 'assetSourceIdentifier' => null, 'expectedResult' => null],
            ['allowedAssetSourceIdentifiers' => [], 'assetSourceIdentifier' => 'some-id', 'expectedResult' => 'some-id'],
            ['allowedAssetSourceIdentifiers' => ['some-id'], 'assetSourceIdentifier' => 'some-id', 'expectedResult' => 'some-id'],
            ['allowedAssetSourceIdentifiers' => ['some-allowed-id'], 'assetSourceIdentifier' => 'some-disallowed-id', 'expectedResult' => 'some-allowed-id'],
            ['allowedAssetSourceIdentifiers' => ['some-allowed-id', 'some-other-allowed-id'], 'assetSourceIdentifier' => 'some-disallowed-id', 'expectedResult' => 'some-allowed-id'],
            ['allowedAssetSourceIdentifiers' => ['some-allowed-id', 'some-other-allowed-id'], 'assetSourceIdentifier' => 'some-allowed-id', 'expectedResult' => 'some-allowed-id'],
            ['allowedAssetSourceIdentifiers' => ['some-allowed-id', 'some-other-allowed-id'], 'assetSourceIdentifier' => 'some-other-allowed-id', 'expectedResult' => 'some-other-allowed-id'],
        ];
    }

    /**
     * @param array $allowedAssetSourceIdentifiers
     * @param string|null $assetSourceIdentifier
     * @param string|null $expectedResult
     * @test
     * @dataProvider applyToAssetSourceIdentifiersDataProvider
     */
    public function applyToAssetSourceIdentifiersTests(array $allowedAssetSourceIdentifiers, string $assetSourceIdentifier = null, string $expectedResult = null): void
    {
        $constraints = AssetConstraints::create()->withAssetSourceConstraint($allowedAssetSourceIdentifiers);
        self::assertSame($expectedResult, $constraints->applyToAssetSourceIdentifiers($assetSourceIdentifier));
    }

    public function applyToAssetTypeFilterDataProvider(): array
    {
        return [
            ['mediaTypes' => [], 'assetType' => null, 'expectedResult' => 'All'],
            ['mediaTypes' => [], 'assetType' => 'All', 'expectedResult' => 'All'],
            ['mediaTypes' => [], 'assetType' => 'Image', 'expectedResult' => 'Image'],
            ['mediaTypes' => ['image/*'], 'assetType' => 'Image', 'expectedResult' => 'Image'],
            ['mediaTypes' => ['audio/*', 'image/*'], 'assetType' => 'Image', 'expectedResult' => 'Image'],
            ['mediaTypes' => ['audio/*', 'image/*'], 'assetType' => null, 'expectedResult' => 'Audio'],
            ['mediaTypes' => ['audio/*', 'image/*'], 'assetType' => 'Video', 'expectedResult' => 'Audio'],
            ['mediaTypes' => ['audio/*', 'image/*'], 'assetType' => 'All', 'expectedResult' => 'Audio'],
        ];
    }

    /**
     * @param array $allowedMediaTypes
     * @param string|null $assetType
     * @param string $expectedResult
     * @test
     * @dataProvider applyToAssetTypeFilterDataProvider
     */
    public function applyToAssetTypeFilterTests(array $allowedMediaTypes, ?string $assetType, string $expectedResult): void
    {
        $constraints = AssetConstraints::create()->withMediaTypeConstraint($allowedMediaTypes);
        self::assertSame($expectedResult, (string)$constraints->applyToAssetTypeFilter($assetType));
    }

    public function getAllowedAssetTypeFilterOptionsDataProvider(): array
    {
        return [
            ['mediaTypes' => [], 'expectedResult' => ['All', 'Image', 'Document', 'Video', 'Audio']],
            ['mediaTypes' => ['image/*'], 'expectedResult' => []],
            ['mediaTypes' => ['unknown/media-type'], 'expectedResult' => []],
            ['mediaTypes' => ['video/*', 'image/*'], 'expectedResult' => ['Video', 'Image']],
            ['mediaTypes' => ['video/*', 'image/jpeg', 'image/gif'], 'expectedResult' => ['Video', 'Image']],
            ['mediaTypes' => ['unknown/media-type', 'video/*'], 'expectedResult' => ['Document', 'Video']],
        ];
    }

    /**
     * @param array $allowedMediaTypes
     * @param array $expectedResult
     * @test
     * @dataProvider getAllowedAssetTypeFilterOptionsDataProvider
     */
    public function getAllowedAssetTypeFilterOptionsTests(array $allowedMediaTypes, array $expectedResult): void
    {
        $constraints = AssetConstraints::create()->withMediaTypeConstraint($allowedMediaTypes);
        self::assertSame($expectedResult, $constraints->getAllowedAssetTypeFilterOptions());
    }
}
