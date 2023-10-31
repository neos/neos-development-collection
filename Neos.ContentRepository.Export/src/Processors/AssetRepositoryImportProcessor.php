<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Processors;

use League\Flysystem\Filesystem;
use League\Flysystem\StorageAttributes;
use Neos\ContentRepository\Export\Asset\ValueObject\AssetType;
use Neos\ContentRepository\Export\Asset\ValueObject\ImageAdjustmentType;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Export\Severity;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\Adjustment\QualityImageAdjustment;
use Neos\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Audio;
use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Model\Video;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\ObjectAccess;

/**
 * Processor that imports assets and resources from the filesystem to the Asset- and ResourceRepository
 *
 * Note: This processor requires the package "neos/media" to be installed!
 */
final class AssetRepositoryImportProcessor implements ProcessorInterface
{
    private array $callbacks = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly AssetRepository $assetRepository,
        private readonly ResourceRepository $resourceRepository,
        private readonly ResourceManager $resourceManager,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {}

    public function onMessage(\Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    public function run(): ProcessorResult
    {
        $this->persistenceManager->clearState();
        $numberOfErrors = 0;
        $numberOfImportedAssets = 0;
        foreach ($this->files->listContents('/Assets') as $file) {
            if (!$file->isFile()) {
                continue;
            }
            try {
                $this->importAsset($file);
                $numberOfImportedAssets ++;
            } catch (\Throwable $e) {
                $numberOfErrors ++;
                $this->dispatch(Severity::ERROR, 'Failed to import asset from file "%s": %s', $file->path(), $e->getMessage());
            }
        }
        $numberOfImportedImageVariants = 0;
        foreach ($this->files->listContents('/ImageVariants') as $file) {
            if (!$file->isFile()) {
                continue;
            }
            try {
                $this->importImageVariant($file);
                $numberOfImportedImageVariants ++;
            } catch (\Throwable $e) {
                $numberOfErrors ++;
                $this->dispatch(Severity::ERROR, 'Failed to import image variant from file "%s": %s', $file->path(), $e->getMessage());
            }
        }
        return ProcessorResult::success(sprintf('Imported %d Asset%s and %d Image Variant%s. Errors: %d', $numberOfImportedAssets, $numberOfImportedAssets === 1 ? '' : 's', $numberOfImportedImageVariants, $numberOfImportedImageVariants === 1 ? '' : 's', $numberOfErrors));
    }

    /** --------------------------------------- */

    private function importAsset(StorageAttributes $file): void
    {
        $fileContents = $this->files->read($file->path());
        $serializedAsset = SerializedAsset::fromJson($fileContents);
        /** @var Asset|null $existingAsset */
        $existingAsset = $this->assetRepository->findByIdentifier($serializedAsset->identifier);
        if ($existingAsset !== null) {
            if ($serializedAsset->matches($existingAsset)) {
                $this->dispatch(Severity::NOTICE, 'Asset "%s" was skipped because it already exists!', $serializedAsset->identifier);
            } else {
                $this->dispatch(Severity::ERROR, 'Asset "%s" has been changed in the meantime, it was NOT updated!', $serializedAsset->identifier);
            }
            return;
        }
        /** @var PersistentResource|null $resource */
        $resource = $this->resourceRepository->findBySha1AndCollectionName($serializedAsset->resource->sha1, $serializedAsset->resource->collectionName)[0] ?? null;
        if ($resource === null) {
            $content = $this->files->read('/Resources/' . $serializedAsset->resource->sha1);
            $resource = $this->resourceManager->importResourceFromContent($content, $serializedAsset->resource->filename, $serializedAsset->resource->collectionName);
            $resource->setMediaType($serializedAsset->resource->mediaType);
        }
        $asset = match ($serializedAsset->type) {
            AssetType::IMAGE => new Image($resource),
            AssetType::AUDIO => new Audio($resource),
            AssetType::DOCUMENT => new Document($resource),
            AssetType::VIDEO => new Video($resource),
        };
        // HACK There is currently no other way to set the persistence object id of assets
        ObjectAccess::setProperty($asset, 'Persistence_Object_Identifier', $serializedAsset->identifier, true);
        $asset->setTitle($serializedAsset->title);
        $asset->setCaption($serializedAsset->caption);
        $this->assetRepository->add($asset);
        $this->persistenceManager->persistAll();
    }

    private function importImageVariant(StorageAttributes $file): void
    {
        $fileContents = $this->files->read($file->path());
        $serializedImageVariant = SerializedImageVariant::fromJson($fileContents);
        $existingImageVariant = $this->assetRepository->findByIdentifier($serializedImageVariant->identifier);
        assert($existingImageVariant === null || $existingImageVariant instanceof ImageVariant);
        if ($existingImageVariant !== null) {
            if ($serializedImageVariant->matches($existingImageVariant)) {
                $this->dispatch(Severity::NOTICE, 'Image Variant "%s" was skipped because it already exists!', $serializedImageVariant->identifier);
            } else {
                $this->dispatch(Severity::ERROR, 'Image Variant "%s" has been changed in the meantime, it was NOT updated!', $serializedImageVariant->identifier);
            }
            return;
        }
        $originalImage = $this->assetRepository->findByIdentifier($serializedImageVariant->originalAssetIdentifier);
        if ($originalImage === null) {
            $this->dispatch(Severity::ERROR, 'Failed to find original asset "%s", skipping image variant "%s"', $serializedImageVariant->originalAssetIdentifier, $serializedImageVariant->identifier);
            return;
        }
        assert($originalImage instanceof Image);
        $imageVariant = new ImageVariant($originalImage);
        ObjectAccess::setProperty($imageVariant, 'Persistence_Object_Identifier', $serializedImageVariant->identifier, true);
        foreach ($serializedImageVariant->imageAdjustments as $serializedAdjustment) {
            $adjustment = match ($serializedAdjustment->type) {
                ImageAdjustmentType::RESIZE_IMAGE => new ResizeImageAdjustment($serializedAdjustment->properties),
                ImageAdjustmentType::CROP_IMAGE => new CropImageAdjustment($serializedAdjustment->properties),
                ImageAdjustmentType::QUALITY_IMAGE => new QualityImageAdjustment($serializedAdjustment->properties),
            };
            $imageVariant->addAdjustment($adjustment);
            $imageVariant->refresh();
        }
        $this->assetRepository->add($imageVariant);
        $this->persistenceManager->persistAll();
    }

    private function dispatch(Severity $severity, string $message, mixed ...$args): void
    {
        $renderedMessage = sprintf($message, ...$args);
        foreach ($this->callbacks as $callback) {
            $callback($severity, $renderedMessage);
        }
    }
}
