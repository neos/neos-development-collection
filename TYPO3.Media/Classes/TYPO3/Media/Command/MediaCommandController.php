<?php
namespace TYPO3\Media\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Utility\MediaTypes;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Repository\ThumbnailRepository;

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
     * @var \TYPO3\Media\Domain\Repository\AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ThumbnailRepository
     */
    protected $thumbnailRepository;

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
