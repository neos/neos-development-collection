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
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceAwareInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Model\VariantSupportInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\ImageVariantRepository;
use Neos\Media\Domain\Repository\ThumbnailRepository;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\AssetVariantGenerator;
use Neos\Media\Domain\Service\ImageVariantService;
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
     * @var ImageVariantRepository
     */
    protected $imageVariantRepository;

    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\Inject
     * @var ImageVariantService
     */
    protected $imageVariantService;

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
            SELECT r.persistence_object_identifier, r.filename, r.mediatype
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
     * @param string $onlyTags Comma-separated list of asset tag labels, that should be taken into account
     * @param int|null $limit Limit the result of unused assets displayed and removed for this run.
     * @param string $onlyCollections Comma-separated list of asset collection titles, that should be taken into account
     * @return void
     * @throws IllegalObjectTypeException
     * @throws AssetServiceException
     */
    public function removeUnusedCommand(string $assetSource = '', bool $quiet = false, bool $assumeYes = false, string $onlyTags = '', int $limit = null, string $onlyCollections = ''): void
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

        $assetCollectionsMatchFilterCollections = static function (Collection $assetCollections, string $filterCollections): bool {
            $filterCollectionValues = Arrays::trimExplode(',', $filterCollections);
            $assetCollectionValues = [];
            foreach ($assetCollections as $assetCollection) {
                /** @var AssetCollection $assetCollection */
                $assetCollectionValues[] = $assetCollection->getTitle();
            }
            return count(array_intersect($filterCollectionValues, $assetCollectionValues)) > 0;
        };

        !$quiet && $this->output->progressStart($assetCount);

        /** @var Asset $asset */
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
            if ($onlyCollections !== '' && !$assetCollectionsMatchFilterCollections($asset->getAssetCollections(), $onlyCollections)) {
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
                exit;
            }
        }

        !$quiet && $this->output->progressStart($unusedAssetCount);
        foreach ($unusedAssets as $asset) {
            !$quiet && $this->output->progressAdvance(1);
            $this->assetRepository->remove($asset);
        }
        !$quiet && $this->output->progressFinish();
        !$quiet && $this->output->outputLine();
    }

    /**
     * Create thumbnails
     *
     * Creates thumbnail images based on the configured thumbnail presets. Optional ``preset`` parameter to only create
     * thumbnails for a specific thumbnail preset configuration.
     *
     * Additionally accepts a ``async`` parameter determining if the created thumbnails are generated when created.
     *
     * @param string|null $preset Preset name, if not provided thumbnails are created for all presets
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
        foreach ($presets as $presetName) {
            $presetThumbnailConfigurations[] = $this->thumbnailService->getThumbnailConfigurationForPreset($presetName, $async);
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
        !$quiet && $this->output->outputLine();
    }

    /**
     * Remove thumbnails
     *
     * Removes all thumbnail objects and their resources. Optional ``preset`` parameter to only remove thumbnails
     * matching a specific thumbnail preset configuration.
     *
     * @param string|null $preset Preset name, if provided only thumbnails matching that preset are cleared
     * @param bool $quiet If set, only errors will be displayed.
     * @return void
     * @throws IllegalObjectTypeException
     * @throws ThumbnailServiceException
     */
    public function clearThumbnailsCommand(string $preset = null, bool $quiet = false): void
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
        foreach ($this->thumbnailRepository->iterate($iterator, function ($iteration) {
            $this->persistAll($iteration);
        }) as $thumbnail) {
            $this->thumbnailRepository->remove($thumbnail);
            !$quiet && $this->output->progressAdvance(1);
        }
        !$quiet && $this->output->progressFinish();
        !$quiet && $this->output->outputLine();
    }

    /**
     * Render ungenerated thumbnails
     *
     * Loops over ungenerated thumbnails and renders them. Optional ``limit`` parameter to limit the amount of
     * thumbnails to be rendered to avoid memory exhaustion.
     *
     * @param int|null $limit Limit the amount of thumbnails to be rendered to avoid memory exhaustion
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
     * Tidy up outdated imageVariants
     *
     * This command iterates over all existing asset variants with outdated preset.
     * They can get filtered and deleted.
     *
     * @param string $assetSource If specified, only assets of this asset source are considered. For example "neos" or "my-asset-management-system".
     * @param bool $quiet If set, only errors and questions will be displayed.
     * @param bool $assumeYes If set, "yes" is assumed for the "shall I remove ..." dialog.
     * @param bool $filterCroppedVariants Find custom crops but have to check if used (slow).
     * @param int|null $limit Limit the result of unused assets displayed and removed for this run.
     * @param string $presetIdentifier If specified, only presets with this identifier are observed.
     */
    public function removeOutdatedVariantsCommand(bool $quiet = false, bool $assumeYes = false, bool $filterCroppedVariants = false, int $limit = null, string $assetSource = '', string $presetIdentifier = '')
    {
        if (empty($presetIdentifier)) {
            // will read all presets defined in 'Settings.Neos.Media.yaml'
            $currentPresets = $this->imageVariantService->getAllPresetsByConfiguration();
        } else {
            // is --preset-identifier used
            $currentPresets = $this->imageVariantService->getAllPresetsOfIdentifier($presetIdentifier);
        }

        if (empty($currentPresets)) {
            !$quiet && $this->output->outputLine(PHP_EOL . PHP_EOL . '<em>No presets found.</em>');
            exit;
        }

        $filterByAssetSourceIdentifier = $assetSource;

        // is --asset-source-filter used
        if (empty($filterByAssetSourceIdentifier)) {
            !$quiet && $this->outputLine(PHP_EOL . '<b>Searching for assets in all sources:</b>');
        } else {
            !$quiet && $this->outputLine(PHP_EOL . '<b>Searching for assets in "%s" source:</b>', [$filterByAssetSourceIdentifier]);
        }

        $variants = $this->imageVariantRepository->findAllWithOutdatedPresets($currentPresets, !empty($presetIdentifier), $filterCroppedVariants, $limit);
        if (empty($variants)) {
            !$quiet && $this->output->outputLine(PHP_EOL . PHP_EOL . '<em>No variants found.</em>');
            exit;
        }

        $outdatedVariants = [];
        $outdatedPresetsCount = [];
        $variantCount = 0;

        !$quiet && $this->outputLine(PHP_EOL . 'Received response...');
        !$quiet && $this->outputLine(PHP_EOL . 'Filtering for deletable ImageVariants...');
        !$quiet && $this->output->progressStart(sizeof($variants));

        foreach ($variants as $variant) {
            // check if necessary for current configuration
            if (!empty($filterByAssetSourceIdentifier) && $variant->getAssetSourceIdentifier() !== $filterByAssetSourceIdentifier) {
                continue;
            }

            // to see how many variants have been found with given configuration
            $variantCount += 1;

            // prettify for user experience
            if ($variant->getPresetIdentifier() && $variant->getPresetVariantName()) {
                $outdatedPresetKey = sprintf('%s:%s', $variant->getPresetIdentifier(), $variant->getPresetVariantName());
            } else {
                // for mapping errors of the variant and better user experience
                $outdatedPresetKey = 'none';
            }
            if (!array_key_exists($outdatedPresetKey, $outdatedPresetsCount)) {
                $outdatedPresetsCount[$outdatedPresetKey] = 0;
            }
            $outdatedPresetsCount[$outdatedPresetKey] += 1;
            $outdatedVariants[] = $variant;

            !$quiet && $this->output->progressAdvance();
        }
        !$quiet && $this->output->progressFinish();

        if (empty($outdatedVariants)) {
            !$quiet && $this->output->outputLine(PHP_EOL . PHP_EOL . '<em>No outdated variants found.</em>');
            exit;
        }

        if (!$quiet) {
            $this->outputLine(PHP_EOL . 'Outdated presets:', [$variantCount]);

            // display all outdated presets to user - nice structured
            for ($i = 0; $i < count($outdatedPresetsCount); $i++) {
                $preset = array_keys($outdatedPresetsCount)[$i];
                $count = $outdatedPresetsCount[$preset];
                $this->outputLine(PHP_EOL . '  [%s] - %s (found: %s)', [$i + 1, $preset, $count]);
            }

            $notLimitedDeletableOutput = PHP_EOL . '<b>There are %s asset variants ready to be deleted.</b>';
            $this->outputLine($notLimitedDeletableOutput, [sizeof($outdatedVariants)]);
            if ($limit) {
                $this->outputLine(' find more by running without the "--limit" parameter');
            }
        }

        // if --assume-yes not used: user decision to delete variants
        if (!$assumeYes) {
            if (empty($variantPresetConfigs)) {
                $this->output->outputLine(PHP_EOL . '<em>No preset configuration found.</em>');
                $this->output->outputLine('<em>You are about to delete all Variants!</em>');
            }
            if (!$this->output->askConfirmation(PHP_EOL . 'Do you want to remove all variants with outdated presets? [Y,n] ')) {
                $this->output->outputLine(PHP_EOL . '<em>No variants have been deleted...</em>');
                exit;
            }
        }

        !$quiet && $this->outputLine(PHP_EOL . PHP_EOL . '<b>Removing selected:</b>');
        !$quiet && $this->output->progressStart(sizeof($outdatedVariants));

        $outdatedVariantSize = 0;
        $stillUsedVariants = 0;
        foreach ($outdatedVariants as $variantToRemove) {
            !$quiet && $this->output->progressAdvance();
            $variantSize = $variantToRemove->getResource()->getFileSize();

            try {
                $variantToRemove->getResource()->disableLifecycleEvents();
                if (!$variantToRemove->getPresetIdentifier() && !$variantToRemove->getPresetVariantName() && $filterCroppedVariants) {
                    // customized variants don't have presetIdentifier or presetVariantName
                    if ($this->assetService->isInUse($variantToRemove)) {
                        // if the variant is customized and in use
                        // it will not be deleted but counted for user notification
                        $stillUsedVariants += 1;
                        continue;
                    }
                }

                $this->assetRepository->removeWithoutUsageChecks($variantToRemove);
                $this->persistenceManager->persistAll();
            } catch (IllegalObjectTypeException $e) {
                $this->output->outputLine(PHP_EOL . 'Unable to remove %s: "%s"', [get_class($variantToRemove), $variantToRemove->getTitle()]);
                $this->output->outputLine(PHP_EOL . $e->getMessage());
                exit;
            }

            $outdatedVariantSize += $variantSize;
        }
        !$quiet && $this->output->progressFinish();

        if (!$quiet) {
            if ($stillUsedVariants > 0) {
                $this->outputLine(PHP_EOL . '<b>Found ' . $stillUsedVariants . ' still used asset variants.</b>');
                $this->outputLine('Those have not been deleted.');
            }

            $readableStorageSize = str_pad(Files::bytesToSizeString($outdatedVariantSize), 9, ' ', STR_PAD_LEFT);
            $this->outputLine(PHP_EOL . PHP_EOL . '<success>Removed ' . $readableStorageSize . '</success>');
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
     * @param int|null $limit Limit the amount of variants to be rendered to avoid memory exhaustion
     * @param bool $quiet If set, only errors will be displayed.
     * @param bool $recreate If set, existing asset variants will be re-generated and replaced
     * @return void
     * @throws AssetVariantGeneratorException
     * @throws IllegalObjectTypeException
     */
    public function renderVariantsCommand(int $limit = null, bool $quiet = false, bool $recreate = false): void
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
     * Used as a callback when iterating large results sets
     */
    protected function persistAll(int $iteration): void
    {
        if ($iteration % 1000 === 0) {
            $this->persistenceManager->persistAll();
        }
    }

    /**
     * Initializes the DBAL connection which is currently bound to the Doctrine Entity Manager
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
