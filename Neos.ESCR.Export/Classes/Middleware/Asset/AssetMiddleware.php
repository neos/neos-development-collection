<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Asset;

use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Neos\ESCR\AssetUsage\AssetUsageFinder;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\Export\Middleware\Asset\ValueObject\SerializedAsset;
use Neos\ESCR\Export\Middleware\Asset\ValueObject\SerializedImageVariant;
use Neos\ESCR\Export\Middleware\Context;
use Neos\ESCR\Export\Middleware\MiddlewareInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\ObjectAccess;

final class AssetMiddleware implements MiddlewareInterface
{

    public function __construct(
        private readonly AssetRepository $assetRepository,
        private readonly ResourceRepository $resourceRepository,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly AssetUsageFinder $assetUsageFinder,
        private readonly ResourceManager $resourceManager,
    ) {}

    public function getLabel(): string
    {
        return 'Assets';
    }

    public function processImport(Context $context): void
    {
        foreach ($context->files->listContents('/Assets') as $file) {
            if (!$file->isFile()) {
                continue;
            }
            try {
                $this->importAsset($context, $file);
            } catch (\Throwable $e) {
                $context->report(sprintf('Failed to import asset from file "%s": %s', $file->path(), $e->getMessage()));
            }
        }
        foreach ($context->files->listContents('/ImageVariants') as $file) {
            if (!$file->isFile()) {
                continue;
            }
            try {
                $this->importImageVariant($context, $file);
            } catch (\Throwable $e) {
                $context->report(sprintf('Failed to import image variant from file "%s": %s', $file->path(), $e->getMessage()));
            }
        }
    }

    /**
     * @param Context $context
     * @param StorageAttributes $file
     * @return void
     * @throws \Throwable
     */
    private function importAsset(Context $context, StorageAttributes $file): void
    {
        $fileContents = $context->files->read($file->path());
        $serializedAsset = SerializedAsset::fromJson($fileContents);
        /** @var Asset|null $existingAsset */
        $existingAsset = $this->assetRepository->findByIdentifier($serializedAsset->identifier);
        if (($existingAsset !== null) && !$serializedAsset->matches($existingAsset)) {
            $context->report(sprintf('Asset "%s" has been changed in the meantime, it was NOT updated!', $serializedAsset->identifier));
            return;
        }
        /** @var PersistentResource|null $resource */
        $resource = $this->resourceRepository->findBySha1AndCollectionName($serializedAsset->resource->sha1, $serializedAsset->resource->collectionName)[0] ?? null;
        if ($resource === null) {
            $content = $context->files->read('/Resources/' . $serializedAsset->resource->sha1);
            $resource = $this->resourceManager->importResourceFromContent($content, $serializedAsset->resource->filename, $serializedAsset->resource->collectionName);
            $resource->setMediaType($serializedAsset->resource->mediaType);
        }
        if (!is_subclass_of($serializedAsset->type, AssetInterface::class)) {
            throw new \RuntimeException(sprintf('The type "%s" referenced in file "%s" does not seem to implement %s', $serializedAsset->type, $file->path(), AssetInterface::class), 1648053958);
        }
        /** @var AssetInterface $asset */
        $asset = new ($serializedAsset->type)($resource);
        $this->assetRepository->add($asset);
    }

    /**
     * @param Context $context
     * @param StorageAttributes $file
     * @throws \Throwable
     */
    private function importImageVariant(Context $context, StorageAttributes $file): void
    {
        $fileContents = $context->files->read($file->path());
        $serializedImageVariant = SerializedImageVariant::fromJson($fileContents);
        $existingImageVariant = $this->assetRepository->findByIdentifier($serializedImageVariant->identifier);
        assert($existingImageVariant === null || $existingImageVariant instanceof ImageVariant);
        if ($existingImageVariant !== null && $existingImageVariant->getOriginalAsset() !== null && !$serializedImageVariant->matches($existingImageVariant)) {
            $context->report(sprintf('Image Variant "%s" has been changed in the meantime, it was NOT updated!', $serializedImageVariant->identifier));
            return;
        }
        $originalImage = $this->assetRepository->findByIdentifier($serializedImageVariant->originalAssetIdentifier);
        if ($originalImage === null) {
            $context->report(sprintf('Failed to find original asset "%s", skipping image variant "%s"', $serializedImageVariant->originalAssetIdentifier, $serializedImageVariant->identifier));
            return;
        }
        assert($originalImage instanceof Image);
        $imageVariant = new ImageVariant($originalImage);
        ObjectAccess::setProperty($imageVariant, 'Persistence_Object_Identifier', $serializedImageVariant->identifier, true);
        foreach ($serializedImageVariant->imageAdjustments as $serializedAdjustment) {
            $adjustment = new ($serializedAdjustment->type)();
            assert($adjustment instanceof ImageAdjustmentInterface);
            foreach ($serializedAdjustment->properties as $propertyName => $propertyValue) {
                ObjectAccess::setProperty($adjustment, $propertyName, $propertyValue);
            }
            $imageVariant->addAdjustment($adjustment);
            $imageVariant->refresh();
        }
        $this->assetRepository->add($imageVariant);
    }

    public function processExport(Context $context): void
    {
        $liveWorkspace = $this->workspaceFinder->findOneByName(WorkspaceName::forLive());
        if ($liveWorkspace === null) {
            $context->report('Failed to find live workspace');
            return;
        }
        $assetFilter = AssetUsageFilter::create()
            ->withContentStream($liveWorkspace->getCurrentContentStreamIdentifier())
            ->groupByAsset();

        foreach ($this->assetUsageFinder->findByFilter($assetFilter) as $assetUsage) {
            /** @var Asset|null $asset */
            $asset = $this->assetRepository->findByIdentifier($assetUsage->assetIdentifier);
            if ($asset === null) {
                $context->report(sprintf('Skipping asset "%s" because it doesn\'t exist in the database', $assetUsage->assetIdentifier));
                continue;
            }

            if ($asset instanceof AssetVariantInterface) {
                /** @var Asset $originalAsset */
                $originalAsset = $asset->getOriginalAsset();
                try {
                    $this->exportAsset($context, $originalAsset);
                } catch (\Throwable $e) {
                    $context->report(sprintf('Failed to export original asset "%s" (for variant "%s"): %s', $originalAsset->getIdentifier(), $asset->getIdentifier(), $e->getMessage()));
                }
            }
            try {
                $this->exportAsset($context, $asset);
            } catch (\Throwable $e) {
                $context->report(sprintf('Failed to export asset "%s": %s', $asset->getIdentifier(), $e->getMessage()));
            }
        }
    }

    /**
     * @param Context $context
     * @param Asset $asset
     * @return void
     * @throws \Throwable
     */
    private function exportAsset(Context $context, Asset $asset): void
    {
        $fileLocation = $asset instanceof ImageVariant ? "ImageVariants/{$asset->getIdentifier()}.json" : "Assets/{$asset->getIdentifier()}.json";
        if ($context->files->has($fileLocation)) {
            return;
        }
        if ($asset instanceof ImageVariant) {
            $context->files->write($fileLocation, SerializedImageVariant::fromImageVariant($asset)->toJson());
            return;
        }
        /** @var PersistentResource|null $resource */
        $resource = $asset->getResource();
        if ($resource === null) {
            $context->report(sprintf('Skipping asset "%s" because the corresponding PersistentResource doesn\'t exist in the database', $asset->getIdentifier()));
            return;
        }
        $context->files->write($fileLocation, SerializedAsset::fromAsset($asset)->toJson());
        $this->exportResource($context, $resource);
    }

    /**
     * @param Context $context
     * @param PersistentResource $resource
     * @return void
     * @throws FilesystemException
     */
    private function exportResource(Context $context, PersistentResource $resource): void
    {
        $fileLocation = "Resources/{$resource->getSha1()}";
        if ($context->files->has($fileLocation)) {
            return;
        }
        $context->files->writeStream($fileLocation, $resource->getStream());
    }
}
