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
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;

/**
 * A repository for Tags
 *
 * @Flow\Scope("singleton")
 */
class TagRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array('label' => QueryInterface::ORDER_ASCENDING);

    /**
     * @param string $searchTerm
     * @return QueryResultInterface
     */
    public function findBySearchTerm($searchTerm)
    {
        $query = $this->createQuery();
        return $query->matching($query->like('label', '%' . $searchTerm . '%'))->execute();
    }

    /**
     * @param array<AssetCollection> $assetCollection
     * @return QueryResultInterface
     */
    public function findByAssetCollections(array $assetCollections)
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
