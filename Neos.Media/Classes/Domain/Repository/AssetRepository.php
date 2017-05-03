<?php
namespace Neos\Media\Domain\Repository;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query\ResultSetMapping;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Service\AssetService;

/**
 * A repository for Assets
 *
 * @Flow\Scope("singleton")
 */
class AssetRepository extends Repository
{
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
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * Find assets by title or given tags
     *
     * @param string $searchTerm
     * @param array $tags
     * @param AssetCollection $assetCollection*
     * @return QueryResultInterface
     */
    public function findBySearchTermOrTags($searchTerm, array $tags = array(), AssetCollection $assetCollection = null)
    {
        $query = $this->createQuery();

        $constraints = array(
            $query->like('title', '%' . $searchTerm . '%'),
            $query->like('resource.filename', '%' . $searchTerm . '%'),
            $query->like('caption', '%' . $searchTerm . '%')
        );
        foreach ($tags as $tag) {
            $constraints[] = $query->contains('tags', $tag);
        }
        $query->matching($query->logicalOr($constraints));
        $this->addImageVariantFilterClause($query);
        $this->addAssetCollectionToQueryConstraints($query, $assetCollection);
        return $query->execute();
    }

    /**
     * Find Assets with the given Tag assigned
     *
     * @param Tag $tag
     * @param AssetCollection $assetCollection
     * @return QueryResultInterface
     */
    public function findByTag(Tag $tag, AssetCollection $assetCollection = null)
    {
        $query = $this->createQuery();
        $query->matching($query->contains('tags', $tag));
        $this->addImageVariantFilterClause($query);
        $this->addAssetCollectionToQueryConstraints($query, $assetCollection);
        return $query->execute();
    }

    /**
     * Counts Assets with the given Tag assigned
     *
     * @param Tag $tag
     * @param AssetCollection $assetCollection
     * @return integer
     */
    public function countByTag(Tag $tag, AssetCollection $assetCollection = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('c', 'c');

        if ($assetCollection === null) {
            $queryString = "SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_asset_tags_join tagmm ON a.persistence_object_identifier = tagmm.media_asset WHERE tagmm.media_tag = ? AND a.dtype != 'neos_media_imagevariant'";
        } else {
            $queryString = "SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_asset_tags_join tagmm ON a.persistence_object_identifier = tagmm.media_asset LEFT JOIN neos_media_domain_model_assetcollection_assets_join collectionmm ON a.persistence_object_identifier = collectionmm.media_asset WHERE tagmm.media_tag = ? AND collectionmm.media_assetcollection = ? AND a.dtype != 'neos_media_imagevariant'";
        }

        $query = $this->entityManager->createNativeQuery($queryString, $rsm);
        $query->setParameter(1, $tag);
        if ($assetCollection !== null) {
            $query->setParameter(2, $assetCollection);
        }
        return $query->getSingleScalarResult();
    }

    /**
     * @return QueryResultInterface
     */
    public function findAll(AssetCollection $assetCollection = null)
    {
        $query = $this->createQuery();
        $this->addImageVariantFilterClause($query);
        $this->addAssetCollectionToQueryConstraints($query, $assetCollection);
        return $query->execute();
    }

    /**
     * @return integer
     */
    public function countAll()
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('c', 'c');

        $queryString = "SELECT count(persistence_object_identifier) c FROM neos_media_domain_model_asset WHERE dtype != 'neos_media_imagevariant'";

        $query = $this->entityManager->createNativeQuery($queryString, $rsm);
        return $query->getSingleScalarResult();
    }

    /**
     * Find Assets without any tag
     *
     * @param AssetCollection $assetCollection
     * @return QueryResultInterface
     */
    public function findUntagged(AssetCollection $assetCollection = null)
    {
        $query = $this->createQuery();
        $query->matching($query->isEmpty('tags'));
        $this->addImageVariantFilterClause($query);
        $this->addAssetCollectionToQueryConstraints($query, $assetCollection);
        return $query->execute();
    }

    /**
     * Counts Assets without any tag
     *
     * @param AssetCollection $assetCollection
     * @return integer
     */
    public function countUntagged(AssetCollection $assetCollection = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('c', 'c');

        if ($assetCollection === null) {
            $queryString = "SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_asset_tags_join tagmm ON a.persistence_object_identifier = tagmm.media_asset WHERE tagmm.media_asset IS NULL AND a.dtype != 'neos_media_imagevariant'";
        } else {
            $queryString = "SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_asset_tags_join tagmm ON a.persistence_object_identifier = tagmm.media_asset LEFT JOIN neos_media_domain_model_assetcollection_assets_join collectionmm ON a.persistence_object_identifier = collectionmm.media_asset WHERE tagmm.media_asset IS NULL AND collectionmm.media_assetcollection = ? AND a.dtype != 'neos_media_imagevariant'";
        }

        $query = $this->entityManager->createNativeQuery($queryString, $rsm);
        if ($assetCollection !== null) {
            $query->setParameter(1, $assetCollection);
        }
        return $query->getSingleScalarResult();
    }

    /**
     * @param AssetCollection $assetCollection
     * @return QueryResultInterface
     */
    public function findByAssetCollection(AssetCollection $assetCollection)
    {
        $query = $this->createQuery();
        $this->addImageVariantFilterClause($query);
        $this->addAssetCollectionToQueryConstraints($query, $assetCollection);
        return $query->execute();
    }

    /**
     * Count assets by asset collection
     *
     * @param AssetCollection $assetCollection
     * @return integer
     */
    public function countByAssetCollection(AssetCollection $assetCollection)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('c', 'c');

        $queryString = "SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_assetcollection_assets_join collectionmm ON a.persistence_object_identifier = collectionmm.media_asset WHERE collectionmm.media_assetcollection = ? AND a.dtype != 'neos_media_imagevariant'";

        $query = $this->entityManager->createNativeQuery($queryString, $rsm);
        $query->setParameter(1, $assetCollection);
        return $query->getSingleScalarResult();
    }

    /**
     * @param Query $query
     * @param AssetCollection $assetCollection
     * @return void
     */
    protected function addAssetCollectionToQueryConstraints(Query $query, AssetCollection $assetCollection = null)
    {
        if ($assetCollection === null) {
            return;
        }

        $constraints = $query->getConstraint();
        $query->matching($query->logicalAnd($constraints, $query->contains('assetCollections', $assetCollection)));
    }

    /**
     * @var Query $query
     * @return QueryInterface
     */
    protected function addImageVariantFilterClause(Query $query)
    {
        $queryBuilder = $query->getQueryBuilder();
        $queryBuilder->andWhere('e NOT INSTANCE OF Neos\Media\Domain\Model\ImageVariant');
        return $query;
    }

    /**
     * @param string $sha1
     * @return AssetInterface|NULL
     */
    public function findOneByResourceSha1($sha1)
    {
        $query = $this->createQuery();
        $query->matching($query->equals('resource.sha1', $sha1, false))->setLimit(1);
        return $query->execute()->getFirst();
    }

    /**
     * Iterate over an IterableResult and return a Generator
     *
     * This method is useful for batch processing huge result set as it clears the object
     * manager and detaches the current object on each iteration.
     *
     * @param IterableResult $iterator
     * @param callable $callback
     * @return \Generator
     */
    public function iterate(IterableResult $iterator, callable $callback = null)
    {
        $iteration = 0;
        foreach ($iterator as $object) {
            $object = current($object);
            yield $object;
            if ($callback !== null) {
                call_user_func($callback, $iteration, $object);
            }
            $iteration++;
        }
    }

    /**
     * Find all objects and return an IterableResult
     *
     * @return IterableResult
     */
    public function findAllIterator()
    {
        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        return $queryBuilder
            ->select('a')
            ->from($this->getEntityClassName(), 'a')
            ->where('a NOT INSTANCE OF Neos\Media\Domain\Model\ImageVariant')
            ->getQuery()->iterate();
    }

    /**
     * Remove an asset while first validating if the object can be removed or
     * if removal is blocked because the asset is still in use.
     *
     * @param AssetInterface $object
     * @return void
     */
    public function remove($object)
    {
        $this->assetService->validateRemoval($object);
        parent::remove($object);
        $this->assetService->emitAssetRemoved($object);
    }

    /**
     * Remove the asset even if it is still in use. Use with care, it is probably
     * better to first make sure the asset is not used anymore and then use
     * the remove() method for removal.
     *
     * @param AssetInterface $object
     * @return void
     */
    public function removeWithoutUsageChecks($object)
    {
        parent::remove($object);
        $this->assetService->emitAssetRemoved($object);
    }

    /**
     * @param AssetInterface $object
     */
    public function add($object)
    {
        parent::add($object);
        $this->assetService->emitAssetCreated($object);
    }

    /**
     * @param AssetInterface $object
     */
    public function update($object)
    {
        parent::update($object);
        $this->assetService->emitAssetUpdated($object);
    }
}
