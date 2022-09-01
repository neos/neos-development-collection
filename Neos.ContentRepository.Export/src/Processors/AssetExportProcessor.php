<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Processors;

use League\Flysystem\Filesystem;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ESCR\AssetUsage\AssetUsageFinder;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * Processor that exports all assets and resources used in the Neos live workspace to the file system
 *
 * Note: This processor requires the packages "neos/media" and "neos/escr-asset-usage" to be installed!
 */
final class AssetExportProcessor implements ProcessorInterface
{
    private array $callbacks = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly AssetRepository $assetRepository,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly AssetUsageFinder $assetUsageFinder,
    ) {}

    public function onMessage(\Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }


    public function run(): ProcessorResult
    {
        $liveWorkspace = $this->workspaceFinder->findOneByName(WorkspaceName::forLive());
        if ($liveWorkspace === null) {
            return ProcessorResult::error('Failed to find live workspace');
        }
        $assetFilter = AssetUsageFilter::create()->withContentStream($liveWorkspace->currentContentStreamId)->groupByAsset();

        $numberOfExportedAssets = 0;
        $numberOfExportedImageVariants = 0;
        $numberOfErrors = 0;

        foreach ($this->assetUsageFinder->findByFilter($assetFilter) as $assetUsage) {
            /** @var Asset|null $asset */
            $asset = $this->assetRepository->findByIdentifier($assetUsage->assetIdentifier);
            if ($asset === null) {
                $numberOfErrors ++;
                $this->dispatch(Severity::ERROR, 'Skipping asset "%s" because it does not exist in the database', $assetUsage->assetIdentifier);
                continue;
            }

            if ($asset instanceof AssetVariantInterface) {
                /** @var Asset $originalAsset */
                $originalAsset = $asset->getOriginalAsset();
                try {
                    $this->exportAsset($originalAsset);
                    $numberOfExportedAssets ++;
                } catch (\Throwable $e) {
                    $numberOfErrors ++;
                    $this->dispatch(Severity::ERROR, 'Failed to export original asset "%s" (for variant "%s"): %s', $originalAsset->getIdentifier(), $asset->getIdentifier(), $e->getMessage());
                }
            }
            try {
                $this->exportAsset($asset);
                if ($asset instanceof AssetVariantInterface) {
                    $numberOfExportedImageVariants ++;
                } else {
                    $numberOfExportedAssets ++;
                }
            } catch (\Throwable $e) {
                $numberOfErrors ++;
                $this->dispatch(Severity::ERROR, 'Failed to export asset "%s": %s', $asset->getIdentifier(), $e->getMessage());
            }
        }
        return ProcessorResult::success(sprintf('Exported %d Asset%s and %d Image Variant%s. Errors: %d', $numberOfExportedAssets, $numberOfExportedAssets === 1 ? '' : 's', $numberOfExportedImageVariants, $numberOfExportedImageVariants === 1 ? '' : 's', $numberOfErrors));
    }

    /** --------------------------------------- */

    private function exportAsset(Asset $asset): void
    {
        $fileLocation = $asset instanceof ImageVariant ? "ImageVariants/{$asset->getIdentifier()}.json" : "Assets/{$asset->getIdentifier()}.json";
        if ($this->files->has($fileLocation)) {
            return;
        }
        if ($asset instanceof ImageVariant) {
            $this->files->write($fileLocation, SerializedImageVariant::fromImageVariant($asset)->toJson());
            return;
        }
        /** @var PersistentResource|null $resource */
        $resource = $asset->getResource();
        if ($resource === null) {
            $this->dispatch(Severity::ERROR, 'Skipping asset "%s" because the corresponding PersistentResource does not exist in the database', $asset->getIdentifier());
            return;
        }
        $this->files->write($fileLocation, SerializedAsset::fromAsset($asset)->toJson());
        $this->exportResource($resource);
    }

    private function exportResource(PersistentResource $resource): void
    {
        $fileLocation = "Resources/{$resource->getSha1()}";
        if ($this->files->has($fileLocation)) {
            return;
        }
        $this->files->writeStream($fileLocation, $resource->getStream());
    }

    private function dispatch(Severity $severity, string $message, mixed ...$args): void
    {
        $renderedMessage = sprintf($message, ...$args);
        foreach ($this->callbacks as $callback) {
            $callback($severity, $renderedMessage);
        }
    }
}
