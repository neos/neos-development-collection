<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Processors;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Severity;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\AssetUsageService;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;

/**
 * Processor that exports all assets and resources used in the Neos live workspace to the file system
 *
 * Note: This processor requires the packages "neos/media" and "neos/neos" to be installed!
 */
final class AssetExportProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly AssetRepository $assetRepository,
        private readonly Workspace $targetWorkspace,
        private readonly AssetUsageService $assetUsageService,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $assetFilter = AssetUsageFilter::create()->withWorkspaceName($this->targetWorkspace->workspaceName)->groupByAsset();

        foreach ($this->assetUsageService->findByFilter($this->contentRepositoryId, $assetFilter) as $assetUsage) {
            /** @var Asset|null $asset */
            $asset = $this->assetRepository->findByIdentifier($assetUsage->assetId);
            if ($asset === null) {
                $context->dispatch(Severity::ERROR, "Skipping asset \"{$assetUsage->assetId}\" because it does not exist in the database");
                continue;
            }

            if ($asset instanceof AssetVariantInterface) {
                /** @var Asset $originalAsset */
                $originalAsset = $asset->getOriginalAsset();
                try {
                    $this->exportAsset($context, $originalAsset);
                } catch (\Throwable $e) {
                    $context->dispatch(Severity::ERROR, "Failed to export original asset \"{$originalAsset->getIdentifier()}\" (for variant \"{$asset->getIdentifier()}\"): {$e->getMessage()}");
                }
            }
            try {
                $this->exportAsset($context, $asset);
            } catch (\Throwable $e) {
                $context->dispatch(Severity::ERROR, "Failed to export asset \"{$asset->getIdentifier()}\": {$e->getMessage()}");
            }
        }
    }

    /** --------------------------------------- */

    private function exportAsset(ProcessingContext $context, Asset $asset): void
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
            $context->dispatch(Severity::ERROR, "Skipping asset \"{$asset->getIdentifier()}\" because the corresponding PersistentResource does not exist in the database");
            return;
        }
        $context->files->write($fileLocation, SerializedAsset::fromAsset($asset)->toJson());
        $this->exportResource($context, $resource);
    }

    private function exportResource(ProcessingContext $context, PersistentResource $resource): void
    {
        $fileLocation = "Resources/{$resource->getSha1()}";
        if ($context->files->has($fileLocation)) {
            return;
        }
        $context->files->writeStream($fileLocation, $resource->getStream());
    }
}
