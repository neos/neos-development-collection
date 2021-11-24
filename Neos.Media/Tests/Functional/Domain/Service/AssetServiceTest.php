<?php
declare(strict_types=1);

namespace Neos\Media\Tests\Functional\Domain\Service;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Media\Domain\Strategy\AssetModelMappingStrategyInterface;
use Neos\Media\Tests\Functional\AbstractTest;

class AssetServiceTest extends AbstractTest
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var AssetModelMappingStrategyInterface
     */
    protected $assetModelMappingStrategy;

    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var AssetService
     */
    protected $assetService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resourceManager = $this->objectManager->get(ResourceManager::class);
        $this->assetModelMappingStrategy = $this->objectManager->get(AssetModelMappingStrategyInterface::class);
        $this->assetService = $this->objectManager->get(AssetService::class);
    }

    public function replaceAssetResourceDataProvider(): array
    {
        return [
            'jpgWithJpg' => [
                'replacementFilePath' => __DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg',
                'options' => [],
            ],
            'jpgWithJpgKeepOriginalFilename' => [
                'replacementFilePath' => __DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg',
                'options' => ['keepOriginalFilename' => true],
            ],
            'jpgWithPng' => [
                'replacementFilePath' => __DIR__ . '/../../Fixtures/Resources/neos_avatar_primary.png',
                'options' => [],
            ],
            'jpgWithPngCreateRedirects' => [
                'replacementFilePath' => __DIR__ . '/../../Fixtures/Resources/neos_avatar_primary.png',
                'options' => ['generateRedirects' => true],
            ]
        ];
    }

    /**
     * @test
     * @dataProvider replaceAssetResourceDataProvider
     *
     * @param string $replacementFilePath
     * @param array $options
     * @throws IllegalObjectTypeException
     * @throws Exception
     */
    public function replaceAssetResource(string $replacementFilePath, array $options): void
    {
        $asset = $this->prepareImportedAsset(__DIR__ . '/../../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg');
        $replacementResource = $this->resourceManager->importResource($replacementFilePath);

        $this->assetService->replaceAssetResource($asset, $replacementResource, $options);
        self::assertEquals($replacementResource, $asset->getResource());
    }

    /**
     * @param string $fileName
     * @return AssetInterface
     * @throws IllegalObjectTypeException
     * @throws Exception
     */
    private function prepareImportedAsset(string $fileName): AssetInterface
    {
        $persistentResource = $this->resourceManager->importResource($fileName);
        $targetType = $this->assetModelMappingStrategy->map($persistentResource);

        /** @var Image $asset */
        $asset = new $targetType($persistentResource);
        $imageVariant = new ImageVariant($asset);
        $asset->addVariant($imageVariant);

        $assetRepository = $this->assetService->getRepository($asset);
        $this->generateThumbnail($asset);

        $assetRepository->add($asset);

        $this->persistenceManager->persistAll();
        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);

        $this->persistenceManager->clearState();

        return $assetRepository->findByIdentifier($assetIdentifier);
    }

    /**
     * @param AssetInterface $asset
     * @throws \Exception
     */
    private function generateThumbnail(AssetInterface $asset): void
    {
        $thumbnailConfiguration = new ThumbnailConfiguration(100);

        /** @var ThumbnailService $thumbnailService */
        $thumbnailService = $this->objectManager->get(ThumbnailService::class);
        $asset->addThumbnail($thumbnailService->getThumbnail($asset, $thumbnailConfiguration));
    }
}
