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

/**
 * A repository for Assets
 *
 * @Flow\Scope("singleton")
 */
class AssetRepository extends \TYPO3\Flow\Persistence\Repository {

	/**
	 * @var array
	 */
	protected $defaultOrderings = array('title' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING);

	/**
	 * Find Assets with the given Tag assigned
	 *
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function findByTag(\TYPO3\Media\Domain\Model\Tag $tag) {
		$query = $this->createQuery();

		return $query->matching($query->contains('tags', $tag))->execute();
	}

	/**
	 * Counts Assets with the given Tag assigned
	 *
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function countByTag(\TYPO3\Media\Domain\Model\Tag $tag) {
		$query = $this->createQuery();

		return $query->matching($query->contains('tags', $tag))->count();
	}

	/**
	 * Find Assets without any tag
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function findUntagged() {
		$query = $this->createQuery();

		return $query->matching($query->isEmpty('tags'))->execute();
	}

	/**
	 * Counts Assets without any tag
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function countUntagged() {
		$query = $this->createQuery();

		return $query->matching($query->isEmpty('tags'))->count();
	}

}
