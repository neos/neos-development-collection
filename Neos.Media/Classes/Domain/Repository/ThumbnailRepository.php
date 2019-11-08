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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailConfiguration;

/**
 * A repository for Thumbnails
 *
 * Note that this repository is not part of the public API. Use the asset's getThumbnail() method instead.
 *
 * @Flow\Scope("singleton")
 */
class ThumbnailRepository extends Repository
{
    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

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
     * @param string $configurationHash Optional filtering by configuration hash (preset)
     * @return IterableResult
     */
    public function findAllIterator($configurationHash = null): IterableResult
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('t')
            ->from($this->getEntityClassName(), 't');
        if ($configurationHash !== null) {
            $queryBuilder
                ->where('t.configurationHash = :configurationHash')
                ->setParameter('configurationHash', $configurationHash);
        }
        return $queryBuilder->getQuery()->iterate();
    }

    /**
     * Find ungenerated objects and return an IterableResult
     *
     * @return IterableResult
     */
    public function findUngeneratedIterator(): IterableResult
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('t')
            ->from($this->getEntityClassName(), 't')
            ->where('t.resource IS NULL AND t.staticResource IS NULL');
        return $queryBuilder->getQuery()->iterate();
    }

    /**
     * Count ungenerated objects
     *
     * @return integer
     */
    public function countUngenerated(): int
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd($query->equals('resource', null), $query->equals('staticResource', null)));
        return $query->count();
    }

    /**
     * Returns a thumbnail of the given asset with the specified dimensions.
     *
     * @param AssetInterface $asset The asset to render a thumbnail for
     * @param ThumbnailConfiguration $configuration
     * @return Thumbnail The thumbnail or NULL
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByAssetAndThumbnailConfiguration(AssetInterface $asset, ThumbnailConfiguration $configuration): ?Thumbnail
    {
        $query = $this->entityManager->createQuery('SELECT t FROM Neos\Media\Domain\Model\Thumbnail t WHERE t.originalAsset = :originalAsset AND t.configurationHash = :configurationHash');
        $query->setParameter('originalAsset', $this->persistenceManager->getIdentifierByObject($asset));
        $query->setParameter('configurationHash', $configuration->getHash());

        $query->setMaxResults(1);
        return $query->getOneOrNullResult();
    }
}
