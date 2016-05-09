<?php
namespace TYPO3\Media\Tests\Unit\Domain\Service;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Repository\AudioRepository;
use TYPO3\Media\Domain\Service\AssetService;
use TYPO3\Media\Domain\Model\Audio;
use TYPO3\Media\Fixtures\AssetTypeWithoutRepository;

require_once(__DIR__ . '/../../Fixtures/AssetTypeWithoutRepository.php');

/**
 * Test case for the Asset Service
 */
class AssetServiceTest extends UnitTestCase
{

    /**
     * @return array
     */
    public function getRepositoryReturnsRepositoryForGivenAssetProvider()
    {
        return [
            [Audio::class, AudioRepository::class],
            [Asset::class, AssetRepository::class],
            [AssetTypeWithoutRepository::class, AssetRepository::class]
        ];
    }

    /**
     * @dataProvider getRepositoryReturnsRepositoryForGivenAssetProvider
     * @test
     */
    public function getRepositoryReturnsRepositoryForGivenAsset($modelClassName, $expectedRepositoryClassName)
    {
        $mockAsset = $this->getMock($modelClassName, [], [], '', false);

        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->getMock($expectedRepositoryClassName)));

        $mockAssetService = $this->getAccessibleMock(AssetService::class, ['dummy'], [], '', false);
        $this->inject($mockAssetService, 'objectManager', $mockObjectManager);

        $repository = $mockAssetService->_call('getRepository', $mockAsset);
        $this->assertInstanceOf($expectedRepositoryClassName, $repository);
    }
}
