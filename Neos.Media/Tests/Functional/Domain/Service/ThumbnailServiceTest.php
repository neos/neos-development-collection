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

use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Model\ThumbnailGenerator\ImageThumbnailGenerator;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Media\Domain\Strategy\ThumbnailGeneratorStrategy;
use Neos\Media\Tests\Functional\AbstractTestCase;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Attributes\Test;

class ThumbnailServiceTest extends AbstractTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @var ThumbnailGeneratorStrategy
     */
    protected $generatorStrategy;

    /**
     * @var array
     */
    protected $originalGeneratorOptions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareTemporaryDirectory();
        $this->prepareResourceManager();
        $this->assetRepository = $this->objectManager->get(AssetRepository::class);
        $this->thumbnailService = $this->objectManager->get(ThumbnailService::class);
        $this->generatorStrategy = $this->objectManager->get(ThumbnailGeneratorStrategy::class);
        $this->originalGeneratorOptions = ObjectAccess::getProperty($this->generatorStrategy, 'generatorOptions', true);
    }

    public function tearDown(): void
    {
        // The strategy is a singleton, so restore the configured options
        ObjectAccess::setProperty($this->generatorStrategy, 'generatorOptions', $this->originalGeneratorOptions, true);
        // Initialize the resource instances before the tables are truncated
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
        parent::tearDown();
    }

    #[Test]
    public function originalIsReturnedIfTheThumbnailWouldEqualTheOriginal(): void
    {
        $image = $this->importImage();

        $thumbnail = $this->thumbnailService->getThumbnail($image, $this->configurationLargerThan($image));

        self::assertSame($image, $thumbnail);
    }

    #[Test]
    public function thumbnailIsGeneratedIfAGeneratorProcessesUnmodifiedAssets(): void
    {
        $generatorOptions = $this->originalGeneratorOptions;
        $generatorOptions[ImageThumbnailGenerator::class]['processUnmodifiedAssets'] = true;
        ObjectAccess::setProperty($this->generatorStrategy, 'generatorOptions', $generatorOptions, true);

        $image = $this->importImage();

        $thumbnail = $this->thumbnailService->getThumbnail($image, $this->configurationLargerThan($image));

        self::assertInstanceOf(Thumbnail::class, $thumbnail);
        self::assertSame($image, $thumbnail->getOriginalAsset());
    }

    #[Test]
    public function disabledGeneratorsDoNotProcessUnmodifiedAssets(): void
    {
        $generatorOptions = $this->originalGeneratorOptions;
        $generatorOptions[ImageThumbnailGenerator::class]['processUnmodifiedAssets'] = true;
        $generatorOptions[ImageThumbnailGenerator::class]['disable'] = true;
        ObjectAccess::setProperty($this->generatorStrategy, 'generatorOptions', $generatorOptions, true);

        $image = $this->importImage();

        $thumbnail = $this->thumbnailService->getThumbnail($image, $this->configurationLargerThan($image));

        self::assertSame($image, $thumbnail);
    }

    private function importImage(): Image
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg');
        $image = new Image($resource);
        $this->assetRepository->add($image);
        $this->persistenceManager->persistAll();
        return $image;
    }

    /**
     * A configuration whose rendered result would be identical to the original:
     * maximum dimensions above the original size, no upscaling, quality or format.
     */
    private function configurationLargerThan(Image $image): ThumbnailConfiguration
    {
        return new ThumbnailConfiguration(
            null,
            $image->getWidth() * 2,
            null,
            $image->getHeight() * 2
        );
    }
}
