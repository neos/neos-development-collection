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
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\QueryResultInterface;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Media\Domain\Model\AssetInterface;

/**
 * A repository for Assets
 *
 * @Flow\Scope("singleton")
 */
class AssetRepository extends Repository {

	/**
	 * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
	 * interface ...
	 *
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array('lastModified' => QueryInterface::ORDER_DESCENDING);

	/**
	 * Find assets by title or given tags
	 *
	 * @param string $searchTerm
	 * @param array $tags
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function findBySearchTermOrTags($searchTerm, array $tags = array()) {
		$query = $this->createQuery();

		$constraints = array(
			$query->like('title', '%' . $searchTerm . '%'),
			$query->like('resource.filename', '%' . $searchTerm . '%')
		);
		foreach ($tags as $tag) {
			$constraints[] = $query->contains('tags', $tag);
		}

		$query->matching($query->logicalOr($constraints));
		$this->addImageVariantFilterClause($query);
		return $query->execute();
	}

	/**
	 * Find Assets with the given Tag assigned
	 *
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @return QueryResultInterface
	 */
	public function findByTag(\TYPO3\Media\Domain\Model\Tag $tag) {
		$query = $this->createQuery();
		$query->matching($query->contains('tags', $tag));
		$this->addImageVariantFilterClause($query);
		return $query->execute();
	}

	/**
	 * Find Assets without any Tag
	 *
	 * @return QueryResultInterface
	 */
	public function findUntagged() {
		$query = $this->createQuery();
		$query->matching($query->isEmpty('tags'));
		$this->addImageVariantFilterClause($query);
		return $query->execute();
	}

	/**
	 * Find Assets without any Tag
	 *
	 * @return QueryResultInterface
	 */
	public function countUntagged() {
		$query = $this->createQuery();
		$query->matching($query->isEmpty('tags'));
		$this->addImageVariantFilterClause($query);
		return $query->count();
	}

	/**
	 * Counts Assets with the given Tag assigned
	 *
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @return integer
	 */
	public function countByTag(\TYPO3\Media\Domain\Model\Tag $tag) {
		$rsm = new \Doctrine\ORM\Query\ResultSetMapping();
		$rsm->addScalarResult('c', 'c');
		$query = $this->entityManager->createNativeQuery('SELECT count(a.persistence_object_identifier) c FROM typo3_media_domain_model_asset a LEFT JOIN typo3_media_domain_model_asset_tags_join mm ON a.persistence_object_identifier = mm.media_asset WHERE mm.media_tag = ?', $rsm);
		$query->setParameter(1, $tag);
		return $query->getSingleScalarResult();
	}

	/**
	 * @return QueryResultInterface
	 */
	public function findAll() {
		$query = $this->createQuery();
		$this->addImageVariantFilterClause($query);
		return $query->execute();
	}

	/**
	 * @return integer
	 */
	public function countAll() {
		$query = $this->createQuery();
		$this->addImageVariantFilterClause($query);
		return $query->count();
	}

	/**
	 * @var \TYPO3\Flow\Persistence\Doctrine\Query $query
	 * @return QueryInterface
	 */
	protected function addImageVariantFilterClause($query) {
		$queryBuilder = $query->getQueryBuilder();
		$queryBuilder->andWhere('e NOT INSTANCE OF TYPO3\Media\Domain\Model\ImageVariant');
		return $query;
	}

	/**
	 * @param string $sha1
	 * @return AssetInterface|NULL
	 */
	public function findOneByResourceSha1($sha1) {
		$query = $this->createQuery();
		$query->matching($query->equals('resource.sha1', $sha1, FALSE))->setLimit(1);
		return $query->execute()->getFirst();
	}
}
