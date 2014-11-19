<?php
namespace TYPO3\Neos\Controller\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Model\ImageVariant;

/**
 *
 *
 * @Flow\Scope("singleton")
 */
class ImageController extends ActionController {

	const THUMBNAIL_MAXIMUM_WIDTH = 1024;
	const THUMBNAIL_MAXIMUM_HEIGHT = 768;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * Upload a new image
	 *
	 * @param Image $image
	 * @return string
	 */
	public function uploadAction(Image $image) {
		$this->assetRepository->add($image);
		$imageVariant = new ImageVariant($image);
		$this->assetRepository->add($imageVariant);

		$thumbnail = $image->getThumbnail(self::THUMBNAIL_MAXIMUM_WIDTH, self::THUMBNAIL_MAXIMUM_HEIGHT);

		$this->response->setHeader('Content-Type', 'application/json');
		return json_encode(
			array(
				'__identity' => $this->persistenceManager->getIdentifierByObject($image),
				'__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($image->getResource()),
				'width' => $image->getWidth(),
				'height' => $image->getHeight(),
				'thumbnail' => array(
					'__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($thumbnail->getResource()),
					'width' => $thumbnail->getWidth(),
					'height' => $thumbnail->getHeight(),
				),
				'variants' => array(
					array(
						'__identity' => $this->persistenceManager->getIdentifierByObject($imageVariant),
						'__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($imageVariant->getResource()),
						'width' => $imageVariant->getWidth(),
						'height' => $imageVariant->getHeight(),
					)
				)
			)
		);
	}

	/**
	 * Fetch the metadata for a given image variant
	 *
	 * @param ImageVariant $imageVariant
	 * @return string
	 */
	public function showImageVariantAction(ImageVariant $imageVariant) {
		$this->response->setHeader('Content-Type', 'application/json');
		$originalImageThumbnail = $imageVariant->getOriginalAsset()->getThumbnail(self::THUMBNAIL_MAXIMUM_WIDTH, self::THUMBNAIL_MAXIMUM_HEIGHT);

		return json_encode(
			array(
				'__identity' => $this->persistenceManager->getIdentifierByObject($imageVariant),
				'width' => $imageVariant->getWidth(),
				'height' => $imageVariant->getHeight(),
				// TODO: adjustments
				'originalAsset' => array(
					'width' => $imageVariant->getOriginalAsset()->getWidth(),
					'height' => $imageVariant->getOriginalAsset()->getHeight(),
					'thumbnail' => array(
						'__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($originalImageThumbnail->getResource()),
						'width' => $originalImageThumbnail->getWidth(),
						'height' => $originalImageThumbnail->getHeight(),
					)
				),
			)
		);
	}

}
