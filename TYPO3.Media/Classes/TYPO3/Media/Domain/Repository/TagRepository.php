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
use TYPO3\Flow\Persistence\QueryResultInterface;

/**
 * A repository for Tags
 *
 * @Flow\Scope("singleton")
 */
class TagRepository extends \TYPO3\Flow\Persistence\Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array('label' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING);

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
