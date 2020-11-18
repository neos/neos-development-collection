<?php
declare(strict_types=1);

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
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Exception\AssetServiceException;

/**
 * A repository for Assets
 *
 * @Flow\Scope("singleton")
 */
class AssetRepository extends Repository
{
    /**
     * Doctrine's Entity Manager.
     *
     * @Flow\Inject
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $defaultOrderings = ['lastModified' => QueryInterface::ORDER_DESCENDING];

    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Find assets by title or given tags
     *
     * @param string $searchTerm
     * @param array $tags
     * @param AssetCollection $assetCollection
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findBySearchTermOrTags($searchTerm, array $tags = [], AssetCollection $assetCollection = null): QueryResultInterface
    {
        $query = $this->createQuery();

        $constraints = [
            $query->like('title', '%' . $searchTerm . '%'),
            $query->like('resource.filename', '%' . $searchTerm . '%'),
            $query->like('caption', '%' . $searchTerm . '%')
        ];
        foreach ($tags as $tag) {
            $constraints[] = $query->contains('tags', $tag);
        }
        $query->matching($query->logicalOr($constraints));
        $this->addAssetVariantFilterClause($query);
        $this->addAssetCollectionToQueryConstraints($query, $assetCollection);
        return $query->execute();
    }

    /**
     * Find Assets with the given Tag assigned
     *
     * @param Tag $tag
     * @param AssetCollection $assetCollection
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findByTag(Tag $tag, AssetCollection $assetCollection = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->contains('tags', $tag));
        $this->addAssetVariantFilterClause($query);
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
    public function countByTag(Tag $tag, AssetCollection $assetCollection = null): int
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('c', 'c');

        if ($assetCollection === null) {
            $queryString = 'SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_asset_tags_join tagmm ON a.persistence_object_identifier = tagmm.media_asset WHERE tagmm.media_tag = ? AND ' . $this->getAssetVariantFilterClauseForDql('a');
        } else {
            $queryString = 'SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_asset_tags_join tagmm ON a.persistence_object_identifier = tagmm.media_asset LEFT JOIN neos_media_domain_model_assetcollection_assets_join collectionmm ON a.persistence_object_identifier = collectionmm.media_asset WHERE tagmm.media_tag = ? AND collectionmm.media_assetcollection = ? AND ' . $this->getAssetVariantFilterClauseForDql('a');
        }

        $query = $this->entityManager->createNativeQuery($queryString, $rsm);
        $query->setParameter(1, $tag);
        if ($assetCollection !== null) {
            $query->setParameter(2, $assetCollection);
        }
        try {
            return (int)$query->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * @param AssetCollection|null $assetCollection
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findAll(AssetCollection $assetCollection = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $this->addAssetVariantFilterClause($query);
        $this->addAssetCollectionToQueryConstraints($query, $assetCollection);
        return $query->execute();
    }

    /**
     * @return integer
     */
    public function countAll(): int
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('c', 'c');

        if ($this->entityClassName === Asset::class) {
            $queryString = 'SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a WHERE ' . $this->getAssetVariantFilterClauseForDql('a');
        } else {
            $queryString = sprintf(
                "SELECT count(persistence_object_identifier) c FROM neos_media_domain_model_asset WHERE dtype = '%s'",
                strtolower(str_replace('Domain_Model_', '', str_replace('\\', '_', $this->entityClassName)))
            );
        }

        $query = $this->entityManager->createNativeQuery($queryString, $rsm);
        try {
            return (int)$query->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * Find Assets without any tag
     *
     * @param AssetCollection $assetCollection
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findUntagged(AssetCollection $assetCollection = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->isEmpty('tags'));
        $this->addAssetVariantFilterClause($query);
        $this->addAssetCollectionToQueryConstraints($query, $assetCollection);
        return $query->execute();
    }

    /**
     * Counts Assets without any tag
     *
     * @param AssetCollection $assetCollection
     * @return integer
     */
    public function countUntagged(AssetCollection $assetCollection = null): int
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('c', 'c');

        if ($assetCollection === null) {
            $queryString = 'SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_asset_tags_join tagmm ON a.persistence_object_identifier = tagmm.media_asset WHERE tagmm.media_asset IS NULL AND ' . $this->getAssetVariantFilterClauseForDql('a');
        } else {
            $queryString = 'SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_asset_tags_join tagmm ON a.persistence_object_identifier = tagmm.media_asset LEFT JOIN neos_media_domain_model_assetcollection_assets_join collectionmm ON a.persistence_object_identifier = collectionmm.media_asset WHERE tagmm.media_asset IS NULL AND collectionmm.media_assetcollection = ? AND ' . $this->getAssetVariantFilterClauseForDql('a');
        }

        $query = $this->entityManager->createNativeQuery($queryString, $rsm);
        if ($assetCollection !== null) {
            $query->setParameter(1, $assetCollection);
        }
        try {
            return (int)$query->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * @param AssetCollection $assetCollection
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findByAssetCollection(AssetCollection $assetCollection): QueryResultInterface
    {
        $query = $this->createQuery();
        $this->addAssetVariantFilterClause($query);
        $this->addAssetCollectionToQueryConstraints($query, $assetCollection);
        return $query->execute();
    }

    /**
     * Count assets by asset collection
     *
     * @param AssetCollection $assetCollection
     * @return integer
     */
    public function countByAssetCollection(AssetCollection $assetCollection): int
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('c', 'c');

        $queryString = 'SELECT count(a.persistence_object_identifier) c FROM neos_media_domain_model_asset a LEFT JOIN neos_media_domain_model_assetcollection_assets_join collectionmm ON a.persistence_object_identifier = collectionmm.media_asset WHERE collectionmm.media_assetcollection = ? AND ' . $this->getAssetVariantFilterClauseForDql('a');

        $query = $this->entityManager->createNativeQuery($queryString, $rsm);
        $query->setParameter(1, $assetCollection);
        try {
            return (int)$query->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * @param QueryInterface $query
     * @param AssetCollection $assetCollection
     * @return void
     * @throws InvalidQueryException
     */
    protected function addAssetCollectionToQueryConstraints(QueryInterface $query, AssetCollection $assetCollection = null): void
    {
        if ($assetCollection === null) {
            return;
        }

        $constraints = $query->getConstraint();
        $query->matching($query->logicalAnd([$constraints, $query->contains('assetCollections', $assetCollection)]));
    }

    /**
     * Adds conditions filtering any implementation of AssetVariantInterface
     *
     * @param Query $query
     * @return void
     */
    protected function addAssetVariantFilterClause(Query $query): void
    {
        $queryBuilder = $query->getQueryBuilder();

        $variantClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface(AssetVariantInterface::class);
        foreach ($variantClassNames as $variantClassName) {
            $queryBuilder->andWhere('e NOT INSTANCE OF ' . $variantClassName);
        }
    }

    /**
     * Returns a DQL clause filtering any implementation of AssetVariantInterface
     *
     * @return string
     * @var string $alias
     */
    protected function getAssetVariantFilterClauseForDql(string $alias): string
    {
        $variantClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface(AssetVariantInterface::class);
        $discriminatorTypes = array_map(
            [FlowAnnotationDriver::class, 'inferDiscriminatorTypeFromClassName'],
            $variantClassNames
        );

        return sprintf(
            "%s.dtype NOT IN('%s')",
            $alias,
            implode("','", $discriminatorTypes)
        );
    }

    /**
     * @param string $sha1
     * @return AssetInterface|NULL
     */
    public function findOneByResourceSha1($sha1): ?AssetInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('resource.sha1', $sha1))->setLimit(1);
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
    public function iterate(IterableResult $iterator, callable $callback = null): ?\Generator
    {
        $iteration = 0;
        foreach ($iterator as $object) {
            $object = current($object);
            yield $object;
            if ($callback !== null) {
                $callback($iteration, $object);
            }
            $iteration++;
        }
    }

    /**
     * Find all objects and return an IterableResult
     *
     * @return IterableResult
     */
    public function findAllIterator(): IterableResult
    {
        /** @var Query $query */
        $query = $this->createQuery();
        $this->addAssetVariantFilterClause($query);

        return $query->getQueryBuilder()->getQuery()->iterate();
    }

    /**
     * Remove an asset while first validating if the object can be removed or
     * if removal is blocked because the asset is still in use.
     *
     * @param AssetInterface $object
     * @return void
     * @throws IllegalObjectTypeException
     * @throws AssetServiceException
     */
    public function remove($object): void
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
     * @throws IllegalObjectTypeException
     */
    public function removeWithoutUsageChecks($object): void
    {
        parent::remove($object);
        $this->assetService->emitAssetRemoved($object);
    }

    /**
     * @param AssetInterface $object
     * @throws IllegalObjectTypeException
     */
    public function add($object): void
    {
        parent::add($object);
        $this->assetService->emitAssetCreated($object);
    }

    /**
     * @param AssetInterface $object
     * @throws IllegalObjectTypeException
     */
    public function update($object): void
    {
        parent::update($object);
        $this->assetService->emitAssetUpdated($object);
    }
}
