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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\QueryResult;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\Tag;

/**
 * A repository for Tags
 *
 * @method Tag findOneByLabel(string $label)
 * @method QueryResult findByParent(Tag $tag)
 *
 * @Flow\Scope("singleton")
 */
class TagRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = ['label' => QueryInterface::ORDER_ASCENDING];

    /**
     * @param string $searchTerm
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findBySearchTerm($searchTerm): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query->matching($query->like('label', '%' . $searchTerm . '%'))->execute();
    }

    /**
     * @param array<AssetCollection> $assetCollections
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findByAssetCollections(array $assetCollections): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [];
        foreach ($assetCollections as $assetCollection) {
            $constraints[] = $query->contains('assetCollections', $assetCollection);
        }
        $query->matching($query->logicalOr($constraints));
        return $query->execute();
    }
}
