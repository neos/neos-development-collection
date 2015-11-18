<?php
namespace TYPO3\Media\Command;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Utility\MediaTypes;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Repository\ImageRepository;
use TYPO3\Media\Domain\Repository\ThumbnailRepository;
use TYPO3\Media\Domain\Service\ThumbnailService;

/**
 * @Flow\Scope("singleton")
 */
class MediaCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $entityManager;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbalConnection;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ImageRepository
     */
    protected $imageRepository;

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
     * Import resources to asset management
     *
     * This command detects Flow "Resource" objects which are not yet available as "Asset" objects and thus don't appear
     * in the asset management. The type of the imported asset is determined by the file extension provided by the
     * Resource object.
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
			FROM typo3_flow_resource_resource r
			LEFT JOIN typo3_media_domain_model_asset a
			ON a.resource = r.persistence_object_identifier
			LEFT JOIN typo3_media_domain_model_thumbnail t
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
            $mediaType = $resourceInfo['mediatype'];

            if (substr($mediaType, 0, 6) === 'image/') {
                $resource = $this->persistenceManager->getObjectByIdentifier($resourceInfo['persistence_object_identifier'], 'TYPO3\Flow\Resource\Resource');
                if ($resource === null) {
                    $this->outputLine('Warning: Resource for file "%s" seems to be corrupt. No resource object with identifier %s could be retrieved from the Persistence Manager.', array($resourceInfo['filename'], $resourceInfo['persistence_object_identifier']));
                    continue;
                }
                if (!$resource->getStream()) {
                    $this->outputLine('Warning: Resource for file "%s" seems to be corrupt. The actual data of resource %s could not be found in the resource storage.', array($resourceInfo['filename'], $resourceInfo['persistence_object_identifier']));
                    continue;
                }
                $image = new Image($resource);
                if ($simulate) {
                    $this->outputLine('Simulate: Adding new image "%s" (%sx%s px)', array($image->getResource()->getFilename(), $image->getWidth(), $image->getHeight()));
                } else {
                    $this->assetRepository->add($image);
                    $this->outputLine('Adding new image "%s" (%sx%s px)', array($image->getResource()->getFilename(), $image->getWidth(), $image->getHeight()));
                }
            }
        }
    }

    /**
     * Generate thumbnails for thumbnail presets
     *
     * @param string $preset Preset name, if not provided thumbnails are created for all presets
     * @param boolean $async Asynchronous generation, if not provided the setting ``TYPO3.Media.asyncThumbnails`` is used
     * @return void
     */
    public function createThumbnailsCommand($preset = null, $async = null)
    {
        $async = $async !== null ? $async : $this->asyncThumbnails;
        $presets = $preset !== null ? [$preset] : array_keys($this->thumbnailService->getPresets());
        $presetThumbnailConfigurations = [];
        foreach ($presets as $preset) {
            $presetThumbnailConfigurations[] = $this->thumbnailService->getThumbnailConfigurationForPreset($preset);
        }
        $iterator = $this->imageRepository->findAllIterator();
        $imageCount = $this->imageRepository->countAll();
        $this->output->progressStart($imageCount * count($presetThumbnailConfigurations));
        foreach ($this->imageRepository->iterate($iterator) as $image) {
            foreach ($presetThumbnailConfigurations as $presetThumbnailConfiguration) {
                $this->thumbnailService->getThumbnail($image, $presetThumbnailConfiguration, $async);
                $this->persistenceManager->persistAll();
                $this->output->progressAdvance(1);
            }
        }
    }

    /**
     * Remove all thumbnail objects and resources
     */
    public function clearThumbnailsCommand()
    {
        $thumbnailCount = $this->thumbnailRepository->countAll();
        $this->output->progressStart($thumbnailCount);
        $iterator = $this->thumbnailRepository->findAllIterator();
        foreach ($this->thumbnailRepository->iterate($iterator) as $thumbnail) {
            $this->thumbnailRepository->remove($thumbnail);
            $this->output->progressAdvance(1);
        }
    }

    /**
     * Initializes the DBAL connection which is currently bound to the Doctrine Entity Manager
     *
     * @return void
     */
    protected function initializeConnection()
    {
        if (!$this->entityManager instanceof \Doctrine\ORM\EntityManager) {
            $this->outputLine('This command only supports database connections provided by the Doctrine ORM Entity Manager.
				However, the current entity manager is an instance of %s.', array(get_class($this->entityManager)));
            $this->quit(1);
        }

        $this->dbalConnection = $this->entityManager->getConnection();
    }
}
