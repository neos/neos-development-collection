<?php
namespace TYPO3\TYPO3\Controller\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Controller\Exception\NodeCreationException;

use TYPO3\Flow\Annotations as Flow;

/**
 * The TYPO3 ContentModule controller; providing backend functionality for the Content Module.
 *
 * @Flow\Scope("singleton")
 */
class ContentController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\ImageRepository
	 */
	protected $imageRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * Upload a new image, and return its metadata.
	 *
	 * @param \TYPO3\Media\Domain\Model\Image $image
	 * @return string
	 */
	public function uploadImageAction(\TYPO3\Media\Domain\Model\Image $image) {
		$this->imageRepository->add($image);
		return $this->imageWithMetadataAction($image);
	}

	/**
	 * Fetch the metadata for a given image
	 *
	 * @param \TYPO3\Media\Domain\Model\Image $image
	 * @return string
	 */
	public function imageWithMetadataAction(\TYPO3\Media\Domain\Model\Image $image) {
		$thumbnail = $image->getThumbnail(500, 500);

		return json_encode(array(
			'imageUuid' => $this->persistenceManager->getIdentifierByObject($image),
			'previewImageResourceUri' => $this->resourcePublisher->getPersistentResourceWebUri($thumbnail->getResource()),
			'originalSize' => array('w' => $image->getWidth(), 'h' => $image->getHeight()),
			'previewSize' => array('w' => $thumbnail->getWidth(), 'h' => $thumbnail->getHeight())
		));
	}
}
?>