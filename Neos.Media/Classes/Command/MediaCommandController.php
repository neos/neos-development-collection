<?php
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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetSource\AssetSourceAwareInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\ThumbnailRepository;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Media\Domain\Strategy\AssetModelMappingStrategyInterface;
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
     * If enabled
     * @Flow\InjectConfiguration("asyncThumbnails")
     * @var boolean
     */
    protected $asyncThumbnails;

    /**
     * @Flow\Inject
     * @var AssetModelMappingStrategyInterface
     */
    protected $mappingStrategy;

    /**
     * Import resources to asset management
     *
     * This command detects Flow "PersistentResource"s which are not yet available as "Asset" objects and thus don't appear
     * in the asset management. The type of the imported asset is determined by the file extension provided by the
     * PersistentResource.
     *
     * @param boolean $simulate If set, this command will only tell what it would do instead of doing it right away
     * @return void
     */
    public function importResourcesCommand($simulate = false)
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

        if ($resourceInfos === array()) {
            $this->outputLine('Found no resources which need to be imported.');
            $this->quit();
        }

        foreach ($resourceInfos as $resourceInfo) {
            $resource = $this->persistenceManager->getObjectByIdentifier($resourceInfo['persistence_object_identifier'], PersistentResource::class);

            if ($resource === null) {
                $this->outputLine('Warning: PersistentResource for file "%s" seems to be corrupt. No resource object with identifier %s could be retrieved from the Persistence Manager.', array($resourceInfo['filename'], $resourceInfo['persistence_object_identifier']));
                continue;
            }
            if (!$resource->getStream()) {
                $this->outputLine('Warning: PersistentResource for file "%s" seems to be corrupt. The actual data of resource %s could not be found in the resource storage.', array($resourceInfo['filename'], $resourceInfo['persistence_object_identifier']));
                continue;
            }

            $className = $this->mappingStrategy->map($resource, $resourceInfos);
            $resourceObj = new $className($resource);

            if ($simulate) {
                $this->outputLine('Simulate: Adding new resource "%s" (type: %s)', array($resourceObj->getResource()->getFilename(), $className));
            } else {
                $this->assetRepository->add($resourceObj);
                $this->outputLine('Simulate: Adding new resource "%s" (type: %s)', array($resourceObj->getResource()->getFilename(), $className));
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
     */
    public function removeUnusedCommand(string $assetSource = '', bool $quiet = false, bool $assumeYes = false, string $onlyTags = '', int $limit = 0)
    {
        $iterator = $this->assetRepository->findAllIterator();
        $assetCount = $this->assetRepository->countAll();
        $unusedAssets = [];
        $tableRowsByAssetSource = [];
        $unusedAssetCount = 0;
        $unusedAssetsTotalSize = 0;

        $filterByAssetSourceIdentifier = $assetSource;
        if ($filterByAssetSourceIdentifier === '') {
            !$quiet && $this->outputLine('<b>Searching for unused assets:</b>');
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

            if ($unusedAssetCount === $limit) {
                break;
            }

            if (!($asset instanceof Asset)) {
                continue;
            }
            if (!$asset instanceof AssetSourceAwareInterface) {
                continue;
            }
            if ($filterByAssetSourceIdentifier !== '' && $asset->getAssetSourceIdentifier() !== $filterByAssetSourceIdentifier) {
                continue;
            }
            if (trim($onlyTags) && !($assetTagsMatchFilterTags($asset->getTags(), $onlyTags))) {
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
     * @param boolean $async Asynchronous generation, if not provided the setting ``Neos.Media.asyncThumbnails`` is used
     * @return void
     */
    public function createThumbnailsCommand($preset = null, $async = null)
    {
        $async = $async !== null ? $async : $this->asyncThumbnails;
        $presets = $preset !== null ? [$preset] : array_keys($this->thumbnailService->getPresets());
        $presetThumbnailConfigurations = [];
        foreach ($presets as $preset) {
            $presetThumbnailConfigurations[] = $this->thumbnailService->getThumbnailConfigurationForPreset($preset, $async);
        }
        $iterator = $this->assetRepository->findAllIterator();
        $imageCount = $this->assetRepository->countAll();
        $this->output->progressStart($imageCount * count($presetThumbnailConfigurations));
        foreach ($this->assetRepository->iterate($iterator) as $image) {
            foreach ($presetThumbnailConfigurations as $presetThumbnailConfiguration) {
                $this->thumbnailService->getThumbnail($image, $presetThumbnailConfiguration);
                $this->persistenceManager->persistAll();
                $this->output->progressAdvance(1);
            }
        }
    }

    /**
     * Remove thumbnails
     *
     * Removes all thumbnail objects and their resources. Optional ``preset`` parameter to only remove thumbnails
     * matching a specific thumbnail preset configuration.
     *
     * @param string $preset Preset name, if provided only thumbnails matching that preset are cleared
     * @return void
     */
    public function clearThumbnailsCommand($preset = null)
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
        $this->output->progressStart($thumbnailCount);
        foreach ($this->thumbnailRepository->iterate($iterator) as $thumbnail) {
            $this->thumbnailRepository->remove($thumbnail);
            $this->output->progressAdvance(1);
        }
    }

    /**
     * Render ungenerated thumbnails
     *
     * Loops over ungenerated thumbnails and renders them. Optional ``limit`` parameter to limit the amount of
     * thumbnails to be rendered to avoid memory exhaustion.
     *
     * @param integer $limit Limit the amount of thumbnails to be rendered to avoid memory exhaustion
     * @return void
     */
    public function renderThumbnailsCommand($limit = null)
    {
        $thumbnailCount = $this->thumbnailRepository->countUngenerated();
        $iterator = $this->thumbnailRepository->findUngeneratedIterator();
        $this->output->progressStart($limit !== null && $thumbnailCount > $limit ? $limit : $thumbnailCount);
        $iteration = 0;
        foreach ($this->thumbnailRepository->iterate($iterator) as $thumbnail) {
            if ($thumbnail->getResource() === null) {
                $this->thumbnailService->refreshThumbnail($thumbnail);
                $this->persistenceManager->persistAll();
            }
            $this->output->progressAdvance(1);
            $iteration++;
            if ($iteration === $limit) {
                break;
            }
        }
    }

    /**
     * Initializes the DBAL connection which is currently bound to the Doctrine Entity Manager
     *
     * @return void
     */
    protected function initializeConnection()
    {
        if (!$this->entityManager instanceof EntityManager) {
            $this->outputLine('This command only supports database connections provided by the Doctrine ORM Entity Manager.
				However, the current entity manager is an instance of %s.', array(get_class($this->entityManager)));
            $this->quit(1);
        }

        $this->dbalConnection = $this->entityManager->getConnection();
    }
}
