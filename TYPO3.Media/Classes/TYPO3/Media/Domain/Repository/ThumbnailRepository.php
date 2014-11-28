<?php
namespace TYPO3\Media\Domain\Repository;

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
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Media\Domain\Model\AssetInterface;

/**
 * A repository for Thumbnails
 *
 * Note that this repository is not part of the public API. Use the asset's getThumbnail() method instead.
 *
 * @Flow\Scope("singleton")
 */
class ThumbnailRepository extends Repository {

	/**
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * Returns a thumbnail of the given asset with the specified dimensions.
	 *
	 * @param AssetInterface $asset The asset to render a thumbnail for
	 * @param string $ratioMode The thumbnail's ratio mode, see ImageInterface::RATIOMODE_* constants
	 * @param integer $maximumWidth The thumbnail's maximum width in pixels
	 * @param integer $maximumHeight The thumbnail's maximum height in pixels
	 * @return \TYPO3\Media\Domain\Model\Thumbnail The thumbnail or NULL
	 */
	public function findOneByAssetAndDimensions(AssetInterface $asset, $ratioMode, $maximumWidth = NULL, $maximumHeight = NULL) {

		/**
		 * @var $query \Doctrine\ORM\Query
		 */
		$query = $this->entityManager->createQuery('SELECT t FROM TYPO3\Media\Domain\Model\Thumbnail t WHERE t.originalAsset = :originalAsset AND t.ratioMode = :ratioMode');
		$query->setParameter('originalAsset', $this->persistenceManager->getIdentifierByObject($asset));
		$query->setParameter('ratioMode', $ratioMode);

		if ($maximumWidth !== NULL) {
			$query->setDQL($query->getDQL() . ' AND t.maximumWidth = :maximumWidth');
			$query->setParameter('maximumWidth', $maximumWidth);
		} else {
			$query->setDQL($query->getDQL() . ' AND t.maximumWidth IS NULL');
		}

		if ($maximumHeight !== NULL) {
			$query->setDQL($query->getDQL() . ' AND t.maximumHeight = :maximumHeight');
			$query->setParameter('maximumHeight', $maximumHeight);
		} else {
			$query->setDQL($query->getDQL() . ' AND t.maximumHeight IS NULL');
		}

		$query->setDQL($query->getDQL() . ' LIMIT 1');
		$result = $query->getOneOrNullResult();

		return $result;
	}

}