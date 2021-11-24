<?php
declare(strict_types=1);

namespace Neos\Media\Command;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceAwareInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Model\VariantSupportInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\ThumbnailRepository;
use Neos\Media\Domain\Service\AssetVariantGenerator;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Media\Domain\Strategy\AssetModelMappingStrategyInterface;
use Neos\Media\Exception\AssetServiceException;
use Neos\Media\Exception\AssetVariantGeneratorException;
use Neos\Media\Exception\ThumbnailServiceException;
use Neos\Utility\Arrays;
use Neos\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class MediaCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject(lazy=false)
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var Connection
     */
    protected $dbalConnection;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ThumbnailRepository
     */
    protected $thumbnailRepository;

    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\InjectConfiguration("asyncThumbnails")
     * @var bool
     */
    protected $asyncThumbnails;

    /**
     * @Flow\Inject
     * @var AssetModelMappingStrategyInterface
     */
    protected $mappingStrategy;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var AssetVariantGenerator
     */
    protected $assetVariantGenerator;

    /**
     * Import resources to asset management
     *
     * This command detects Flow "PersistentResource"s which are not yet available as "Asset" objects and thus don't appear
     * in the asset management. The type of the imported asset is determined by the file extension provided by the
     * PersistentResource.
     *
     * @param bool $simulate If set, this command will only tell what it would do instead of doing it right away
     * @param bool $quiet
     * @return void
     * @throws IllegalObjectTypeException
     * @throws DBALException
     */
    public function importResourcesCommand(bool $simulate = false, bool $quiet = false)
    {
        $this->initializeConnection();

        $sql = '
			SELECT
				r.persistence_object_identifier, r.filename, r.mediatype
			FROM neos_flow_resourcemanagement_persistentresource r
			LEFT JOIN neos_media_domain_model_asset a
			ON a.resource = r.persistence_object_identifier
			LEFT JOIN neos_media_domain_model_thumbnail t
			ON t.resource = r.persistence_object_identifier
			WHERE a.persistence_object_identifier IS NULL AND t.persistence_object_identifier IS NULL
		';
        $statement = $this->dbalConnection->prepare($sql);
        $statement->execute();
        $resourceInfos = $statement->fetchAll();

        if ($resourceInfos === []) {
            !$quiet || $this->outputLine('Found no resources which need to be imported.');
            $this->quit();
        }

        foreach ($resourceInfos as $resourceInfo) {
            $resource = $this->persistenceManager->getObjectByIdentifier($resourceInfo['persistence_object_identifier'], PersistentResource::class);

            if ($resource === null) {
                !$quiet || $this->outputLine('Warning: PersistentResource for file "%s" seems to be corrupt. No resource object with identifier %s could be retrieved from the Persistence Manager.', [$resourceInfo['filename'], $resourceInfo['persistence_object_identifier']]);
                continue;
            }
            if (!$resource->getStream()) {
                !$quiet || $this->outputLine('Warning: PersistentResource for file "%s" seems to be corrupt. The actual data of resource %s could not be found in the resource storage.', [$resourceInfo['filename'], $resourceInfo['persistence_object_identifier']]);
                continue;
            }

            $className = $this->mappingStrategy->map($resource, $resourceInfos);
            $resourceObj = new $className($resource);

            if ($simulate) {
                $this->outputLine('Simulate: Adding new resource "%s" (type: %s)', [$resourceObj->getResource()->getFilename(), $className]);
            } else {
                $this->assetRepository->add($resourceObj);
                !$quiet || $this->outputLine('Adding new resource "%s" (type: %s)', [$resourceObj->getResource()->getFilename(), $className]);
            }
        }
    }

    /**
     * Remove unused assets
     *
     * This command iterates over all existing assets, checks their usage count and lists the assets which are not
     * reported as used by any AssetUsageStrategies. The unused assets can than be removed.
     *
     * @param string $assetSource If specified, only assets of this asset source are considered. For example "neos" or "my-asset-management-system"
     * @param bool $quiet If set, only errors will be displayed.
     * @param bool $assumeYes If set, "yes" is assumed for the "shall I remove ..." dialogs
     * @param string $onlyTags Comma-separated list of asset tags, that should be taken into account
     * @param int $limit Limit the result of unused assets displayed and removed for this run.
     * @return void
     * @throws IllegalObjectTypeException
     * @throws AssetServiceException
     */
    public function removeUnusedCommand(string $assetSource = '', bool $quiet = false, bool $assumeYes = false, string $onlyTags = '', int $limit = null)
    {
        $iterator = $this->assetRepository->findAllIterator();
        $assetCount = $this->assetRepository->countAll();
        $unusedAssets = [];
        $tableRowsByAssetSource = [];
        $unusedAssetCount = 0;
        $unusedAssetsTotalSize = 0;

        $filterByAssetSourceIdentifier = $assetSource;
        if ($filterByAssetSourceIdentifier === '') {
            !$quiet && $this->outputLine('<b>Searching for unused assets in all asset sources:</b>');
        } else {
            !$quiet && $this->outputLine('<b>Searching for unused assets of asset source "%s":</b>', [$filterByAssetSourceIdentifier]);
        }

        $assetTagsMatchFilterTags = function (Collection $assetTags, string $filterTags): bool {
            $filterTagValues = Arrays::trimExplode(',', $filterTags);
            $assetTagValues = [];
            foreach ($assetTags as $tag) {
                /** @var Tag $tag */
                $assetTagValues[] = $tag->getLabel();
            }
            return count(array_intersect($filterTagValues, $assetTagValues)) > 0;
        };

        !$quiet && $this->output->progressStart($assetCount);

        foreach ($this->assetRepository->iterate($iterator) as $asset) {
            !$quiet && $this->output->progressAdvance(1);

            if ($limit !== null && $unusedAssetCount === $limit) {
                break;
            }

            if (!$asset instanceof Asset) {
                continue;
            }
            if (!$asset instanceof AssetSourceAwareInterface) {
                continue;
            }
            if ($filterByAssetSourceIdentifier !== '' && $asset->getAssetSourceIdentifier() !== $filterByAssetSourceIdentifier) {
                continue;
            }
            if ($onlyTags !== '' && $assetTagsMatchFilterTags($asset->getTags(), $onlyTags) === false) {
                continue;
            }
            if ($asset->getUsageCount() !== 0) {
                continue;
            }

            $fileSize = str_pad(Files::bytesToSizeString($asset->getResource()->getFileSize()), 9, ' ', STR_PAD_LEFT);

            $unusedAssets[] = $asset;
            $tableRowsByAssetSource[$asset->getAssetSourceIdentifier()][] = [
                $asset->getIdentifier(),
                $asset->getResource()->getFilename(),
                $fileSize
            ];
            $unusedAssetCount++;
            $unusedAssetsTotalSize += $asset->getResource()->getFileSize();
        }

        !$quiet && $this->output->progressFinish();

        if ($unusedAssetCount === 0) {
            !$quiet && $this->output->outputLine(PHP_EOL . 'No unused assets found.');
            exit;
        }

        foreach ($tableRowsByAssetSource as $assetSourceIdentifier => $tableRows) {
            !$quiet && $this->outputLine(PHP_EOL . 'Found the following unused assets from asset source <success>%s</success>: ' . PHP_EOL, [$assetSourceIdentifier]);

            !$quiet && $this->output->outputTable(
                $tableRows,
                ['Asset identifier', 'Filename', 'Size']
            );
        }

        !$quiet && $this->outputLine(PHP_EOL . 'Total size of unused assets: %s' . PHP_EOL, [Files::bytesToSizeString($unusedAssetsTotalSize)]);

        if ($assumeYes === false) {
            if (!$this->output->askConfirmation(sprintf('Do you want to remove <b>%s</b> unused assets?', $unusedAssetCount))) {
                exit(1);
            }
        }

        !$quiet && $this->output->progressStart($unusedAssetCount);
        foreach ($unusedAssets as $asset) {
            !$quiet && $this->output->progressAdvance(1);
            $this->assetRepository->remove($asset);
        }
        !$quiet && $this->output->progressFinish();
    }

    /**
     * Create thumbnails
     *
     * Creates thumbnail images based on the configured thumbnail presets. Optional ``preset`` parameter to only create
     * thumbnails for a specific thumbnail preset configuration.
     *
     * Additionally accepts a ``async`` parameter determining if the created thumbnails are generated when created.
     *
     * @param string $preset Preset name, if not provided thumbnails are created for all presets
     * @param bool $async Asynchronous generation, if not provided the setting ``Neos.Media.asyncThumbnails`` is used
     * @param bool $quiet If set, only errors will be displayed.
     * @return void
     * @throws ThumbnailServiceException
     */
    public function createThumbnailsCommand(string $preset = null, bool $async = null, bool $quiet = false)
    {
        $async = $async ?? $this->asyncThumbnails;
        $presets = $preset !== null ? [$preset] : array_keys($this->thumbnailService->getPresets());
        $presetThumbnailConfigurations = [];
        foreach ($presets as $preset) {
            $presetThumbnailConfigurations[] = $this->thumbnailService->getThumbnailConfigurationForPreset($preset, $async);
        }
        $iterator = $this->assetRepository->findAllIterator();
        $imageCount = $this->assetRepository->countAll();
        !$quiet && $this->output->progressStart($imageCount * count($presetThumbnailConfigurations));
        foreach ($this->assetRepository->iterate($iterator) as $image) {
            foreach ($presetThumbnailConfigurations as $presetThumbnailConfiguration) {
                $this->thumbnailService->getThumbnail($image, $presetThumbnailConfiguration);
                $this->persistenceManager->persistAll();
                !$quiet && $this->output->progressAdvance(1);
            }
        }
        !$quiet && $this->output->progressFinish();
    }

    /**
     * Remove thumbnails
     *
     * Removes all thumbnail objects and their resources. Optional ``preset`` parameter to only remove thumbnails
     * matching a specific thumbnail preset configuration.
     *
     * @param string $preset Preset name, if provided only thumbnails matching that preset are cleared
     * @param bool $quiet If set, only errors will be displayed.
     * @return void
     * @throws IllegalObjectTypeException
     * @throws ThumbnailServiceException
     */
    public function clearThumbnailsCommand(string $preset = null, bool $quiet = false)
    {
        if ($preset !== null) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset);
            $thumbnailConfigurationHash = $thumbnailConfiguration->getHash();
            $thumbnailCount = $this->thumbnailRepository->countByConfigurationHash($thumbnailConfigurationHash);
            $iterator = $this->thumbnailRepository->findAllIterator($thumbnailConfigurationHash);
        } else {
            $thumbnailCount = $this->thumbnailRepository->countAll();
            $iterator = $this->thumbnailRepository->findAllIterator();
        }

        !$quiet && $this->output->progressStart($thumbnailCount);
        foreach ($this->thumbnailRepository->iterate($iterator) as $thumbnail) {
            $this->thumbnailRepository->remove($thumbnail);
            !$quiet && $this->output->progressAdvance(1);
        }
        !$quiet && $this->output->progressFinish();
    }

    /**
     * Render ungenerated thumbnails
     *
     * Loops over ungenerated thumbnails and renders them. Optional ``limit`` parameter to limit the amount of
     * thumbnails to be rendered to avoid memory exhaustion.
     *
     * @param integer $limit Limit the amount of thumbnails to be rendered to avoid memory exhaustion
     * @param bool $quiet If set, only errors will be displayed.
     * @return void
     */
    public function renderThumbnailsCommand(int $limit = null, bool $quiet = false)
    {
        $thumbnailCount = $this->thumbnailRepository->countUngenerated();
        $iterator = $this->thumbnailRepository->findUngeneratedIterator();
        !$quiet && $this->output->progressStart($limit !== null && $thumbnailCount > $limit ? $limit : $thumbnailCount);
        $iteration = 0;
        foreach ($this->thumbnailRepository->iterate($iterator) as $thumbnail) {
            if ($thumbnail->getResource() === null) {
                $this->thumbnailService->refreshThumbnail($thumbnail);
                $this->persistenceManager->persistAll();
            }
            !$quiet && $this->output->progressAdvance(1);
            if (++$iteration === $limit) {
                break;
            }
        }
    }

    /**
     * Render asset variants
     *
     * Loops over missing configured asset variants and renders them. Optional ``limit`` parameter to
     * limit the amount of variants to be rendered to avoid memory exhaustion.
     *
     * If the re-render parameter is given, any existing variants will be rendered again, too.
     *
     * @param integer $limit Limit the amount of variants to be rendered to avoid memory exhaustion
     * @param bool $quiet If set, only errors will be displayed.
     * @param bool $recreate If set, existing asset variants will be re-generated and replaced
     * @return void
     * @throws AssetVariantGeneratorException
     * @throws IllegalObjectTypeException
     */
    public function renderVariantsCommand($limit = null, bool $quiet = false, bool $recreate = false): void
    {
        $resultMessage = null;
        $generatedVariants = 0;
        $configuredVariantsCount = 0;
        $configuredPresets = $this->assetVariantGenerator->getVariantPresets();
        foreach ($configuredPresets as $configuredPreset) {
            $configuredVariantsCount += count($configuredPreset->variants());
        }
        if ($configuredVariantsCount === 0) {
            $this->outputLine('There are no image variant presets configured, exiting…');
            $this->quit();
        }

        $classNames = $this->reflectionService->getAllImplementationClassNamesForInterface(VariantSupportInterface::class);
        foreach ($classNames as $className) {
            /** @var AssetRepository $repository */
            $repositoryClassName = $this->reflectionService->getClassSchema($className)->getRepositoryClassName();
            $repository = $this->objectManager->get($repositoryClassName);

            if (!method_exists($repository, 'findAssetIdentifiersWithVariants')) {
                !$quiet && $this->outputLine('Repository %s does not provide findAssetIdentifiersWithVariants(), skipping…', [$repositoryClassName]);
                continue;
            }

            $assetCount = $repository->countAll();
            $variantCount = $configuredVariantsCount * $assetCount;

            !$quiet && $this->outputLine('Checking up to %u variants for %s for existence…', [$variantCount, $className]);
            !$quiet && $this->output->progressStart($variantCount);

            $currentAsset = null;
            /** @var AssetInterface $currentAsset */
            foreach ($repository->findAssetIdentifiersWithVariants() as $assetIdentifier => $assetVariants) {
                foreach ($configuredPresets as $presetIdentifier => $preset) {
                    foreach ($preset->variants() as $presetVariantName => $presetVariant) {
                        if ($recreate || !isset($assetVariants[$presetIdentifier][$presetVariantName])) {
                            $currentAsset = $repository->findByIdentifier($assetIdentifier);
                            $createdVariant = $recreate ? $this->assetVariantGenerator->recreateVariant($currentAsset, $presetIdentifier, $presetVariantName) : $this->assetVariantGenerator->createVariant($currentAsset, $presetIdentifier, $presetVariantName);
                            if ($createdVariant !== null) {
                                $repository->update($currentAsset);
                                if (++$generatedVariants % 10 === 0) {
                                    $this->persistenceManager->persistAll();
                                }
                                if ($generatedVariants === $limit) {
                                    $resultMessage = sprintf('Generated %u variants, exiting after reaching limit', $limit);
                                    !$quiet && $this->output->progressFinish();
                                    break 3;
                                }
                            }
                        }
                        !$quiet && $this->output->progressAdvance(1);
                    }
                }
            }
            !$quiet && $this->output->progressFinish();
        }

        !$quiet && $this->outputLine();
        !$quiet && $this->outputLine($resultMessage ?? sprintf('Generated %u variants', $generatedVariants));
    }

    /**
     * Initializes the DBAL connection which is currently bound to the Doctrine Entity Manager
     *
     * @return void
     */
    protected function initializeConnection(): void
    {
        if (!$this->entityManager instanceof EntityManager) {
            $this->outputLine('This command only supports database connections provided by the Doctrine ORM Entity Manager.
				However, the current entity manager is an instance of %s.', [get_class($this->entityManager)]);
            $this->quit(1);
        }

        $this->dbalConnection = $this->entityManager->getConnection();
    }
}
