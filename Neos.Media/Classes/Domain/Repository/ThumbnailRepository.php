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

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\Repository;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Psr\Log\LoggerInterface;

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
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

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

    /**
     * store via DBAL to avoid errors caused by concurrent creation of thumbnails - see
     * https://github.com/neos/neos-development-collection/issues/3479#issuecomment-1016375400
     *
     * @param Thumbnail $thumbnail
     * @param ThumbnailConfiguration $configuration
     * @return Thumbnail|null
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function persistThumbnailDirectly(Thumbnail $thumbnail, ThumbnailConfiguration $configuration)
    {
        $thumbnailIdentifier = $this->persistenceManager->getIdentifierByObject($thumbnail);
        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($thumbnail->getOriginalAsset());
        $thumbnailResource = $thumbnail->getResource();

        $sql = 'INSERT INTO neos_media_domain_model_thumbnail (persistence_object_identifier, originalasset, resource, width, height, configuration, configurationhash, staticresource, quality) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $params = [
            $thumbnailIdentifier,
            $assetIdentifier,
            null,
            $thumbnail->getWidth(),
            $thumbnail->getHeight(),
            json_encode($configuration->toArray()),
            $configuration->getHash(),
            $thumbnail->getStaticResource(),
            $thumbnail->getQuality(),
        ];

        $connection = $this->entityManager->getConnection();
        try {
            $affectedRows = $connection->prepare($sql)->executeStatement($params);
            if ($affectedRows === 0) {
                $this->logger->error('Could not persist thumbnail', LogEnvironment::fromMethodName(__METHOD__));
            }
        } catch (DBALException\UniqueConstraintViolationException $e) {
            // ignore, the thumbnail has been generated already; re-fetch
            $this->logger->debug('Persisting thumbnail caused a UniqueConstraintViolationException, ignoring: ' . $e->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
        } catch (DBALException\ForeignKeyConstraintViolationException $e) {
            // TODO
            // why do we end up here, this method is called only if !$this->persistenceManager->isNewObject($asset)
            // but actually that check doesn't even seem to make a difference…
            $this->logger->debug('Persisting thumbnail caused a ForeignKeyConstraintViolationException, ignoring: ' . $e->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
            // ignore, the asset has not been persisted yet, rely on "cascade" persistence later
            return $thumbnail;
        }

        // fetch via ORM to make it known to UoW
        $thumbnail = $this->findOneByAssetAndThumbnailConfiguration($thumbnail->getOriginalAsset(), $configuration);

        // set resource if available and missing on the thumbnail
        if ($thumbnailResource !== null && $thumbnail->getResource() === null) {
            $this->logger->debug('Set resource on re-fetched thumbnail', LogEnvironment::fromMethodName(__METHOD__));
            $thumbnail->setResource($thumbnailResource);
            $this->update($thumbnail);
            // Allow thumbnails to be persisted even if this is a "safe" HTTP request:
            $this->persistenceManager->allowObject($thumbnail);
        }

        return $thumbnail;
    }
}
