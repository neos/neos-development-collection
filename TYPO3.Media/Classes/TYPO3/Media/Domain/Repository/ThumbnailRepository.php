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

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ThumbnailConfiguration;

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
     * @var \Doctrine\Common\Persistence\ObjectManager
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
    public function iterate(IterableResult $iterator, callable $callback = null)
    {
        $iteration = 0;
        foreach ($iterator as $object) {
            $object = current($object);
            yield $object;
            if ($callback !== null) {
                call_user_func($callback, $iteration, $object);
            }
            ++$iteration;
        }
    }

    /**
     * Find all objects and return an IterableResult
     *
     * @return IterableResult
     */
    public function findAllIterator()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        return $queryBuilder
            ->select('Thumbnail')
            ->from($this->getEntityClassName(), 'Thumbnail')
            ->getQuery()->iterate();
    }

    /**
     * Returns a thumbnail of the given asset with the specified dimensions.
     *
     * @param AssetInterface $asset The asset to render a thumbnail for
     * @param ThumbnailConfiguration $configuration
     * @return \TYPO3\Media\Domain\Model\Thumbnail The thumbnail or NULL
     */
    public function findOneByAssetAndThumbnailConfiguration(AssetInterface $asset, ThumbnailConfiguration $configuration)
    {
        /**
         * @var $query \Doctrine\ORM\Query
         */
        $query = $this->entityManager->createQuery('SELECT t FROM TYPO3\Media\Domain\Model\Thumbnail t WHERE t.originalAsset = :originalAsset AND t.configurationHash = :configurationHash');
        $query->setParameter('originalAsset', $this->persistenceManager->getIdentifierByObject($asset));
        $query->setParameter('configurationHash', $configuration->getHash());

        $query->setMaxResults(1);
        $result = $query->getOneOrNullResult();

        return $result;
    }
}
