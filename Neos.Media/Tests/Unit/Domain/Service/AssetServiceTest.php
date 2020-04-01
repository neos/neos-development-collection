<?php
namespace Neos\Media\Tests\Unit\Domain\Service;

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
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Audio;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\AudioRepository;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Fixtures\AssetTypeWithoutRepository;

require_once __DIR__ . '/../../Fixtures/AssetTypeWithoutRepository.php';

/**
 * Test case for the Asset Service
 */
class AssetServiceTest extends UnitTestCase
{

    /**
     * @return array
     */
    public function getRepositoryReturnsRepositoryForGivenAssetProvider(): array
    {
        return [
            [Audio::class, AudioRepository::class],
            [Asset::class, AssetRepository::class],
            [AssetTypeWithoutRepository::class, AssetRepository::class]
        ];
    }

    /**
     * @param $modelClassName
     * @param $expectedRepositoryClassName
     * @dataProvider getRepositoryReturnsRepositoryForGivenAssetProvider
     * @test
     */
    public function getRepositoryReturnsRepositoryForGivenAsset($modelClassName, $expectedRepositoryClassName): void
    {
        $mockAsset = $this->getMockBuilder($modelClassName)->disableOriginalConstructor()->getMock();

        $mockObjectManager = $this->createMock(\Neos\Flow\ObjectManagement\ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())
            ->method('get')
            ->willReturn($this->createMock($expectedRepositoryClassName));

        $mockAssetService = $this->getAccessibleMock(AssetService::class, ['dummy'], [], '', false);
        $this->inject($mockAssetService, 'objectManager', $mockObjectManager);

        $repository = $mockAssetService->_call('getRepository', $mockAsset);
        self::assertInstanceOf($expectedRepositoryClassName, $repository);
    }
}
