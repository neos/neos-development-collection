<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Processors;

use League\Flysystem\StorageAttributes;
use Neos\ContentRepository\Export\Asset\ValueObject\AssetType;
use Neos\ContentRepository\Export\Asset\ValueObject\ImageAdjustmentType;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
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
    public function __construct(
        private readonly AssetRepository $assetRepository,
        private readonly ResourceRepository $resourceRepository,
        private readonly ResourceManager $resourceManager,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $this->persistenceManager->clearState();
        foreach ($context->files->listContents('/Assets') as $file) {
            if (!$file->isFile()) {
                continue;
            }
            try {
                $this->importAsset($context, $file);
            } catch (\Throwable $e) {
                $context->dispatch(Severity::ERROR, "Failed to import asset from file \"{$file->path()}\": {$e->getMessage()}");
            }
        }
        foreach ($context->files->listContents('/ImageVariants') as $file) {
            if (!$file->isFile()) {
                continue;
            }
            try {
                $this->importImageVariant($context, $file);
            } catch (\Throwable $e) {
                $context->dispatch(Severity::ERROR, "Failed to import image variant from file \"{$file->path()}\": {$e->getMessage()}");
            }
        }
    }

    /** --------------------------------------- */

    private function importAsset(ProcessingContext $context, StorageAttributes $file): void
    {
        $fileContents = $context->files->read($file->path());
        $serializedAsset = SerializedAsset::fromJson($fileContents);
        /** @var Asset|null $existingAsset */
        $existingAsset = $this->assetRepository->findByIdentifier($serializedAsset->identifier);
        if ($existingAsset !== null) {
            if ($serializedAsset->matches($existingAsset)) {
                $context->dispatch(Severity::NOTICE, "Asset \"{$serializedAsset->identifier}\" was skipped because it already exists!");
            } else {
                $context->dispatch(Severity::ERROR, "Asset \"{$serializedAsset->identifier}\" has been changed in the meantime, it was NOT updated!");
            }
            return;
        }
        /** @var PersistentResource|null $resource */
        $resource = $this->resourceRepository->findBySha1AndCollectionName($serializedAsset->resource->sha1, $serializedAsset->resource->collectionName)[0] ?? null;
        if ($resource === null) {
            $content = $context->files->read('/Resources/' . $serializedAsset->resource->sha1);
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
        $asset->setCopyrightNotice($serializedAsset->copyrightNotice);
        $this->assetRepository->add($asset);
        $this->persistenceManager->persistAll();
    }

    private function importImageVariant(ProcessingContext $context, StorageAttributes $file): void
    {
        $fileContents = $context->files->read($file->path());
        $serializedImageVariant = SerializedImageVariant::fromJson($fileContents);
        $existingImageVariant = $this->assetRepository->findByIdentifier($serializedImageVariant->identifier);
        assert($existingImageVariant === null || $existingImageVariant instanceof ImageVariant);
        if ($existingImageVariant !== null) {
            if ($serializedImageVariant->matches($existingImageVariant)) {
                $context->dispatch(Severity::NOTICE, "Image Variant \"{$serializedImageVariant->identifier}\" was skipped because it already exists!");
            } else {
                $context->dispatch(Severity::ERROR, "Image Variant \"{$serializedImageVariant->identifier}\" has been changed in the meantime, it was NOT updated!");
            }
            return;
        }
        $originalImage = $this->assetRepository->findByIdentifier($serializedImageVariant->originalAssetIdentifier);
        if ($originalImage === null) {
            $context->dispatch(Severity::ERROR, "Failed to find original asset \"{$serializedImageVariant->originalAssetIdentifier}\", skipping image variant \"{$serializedImageVariant->identifier}\"");
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
}
