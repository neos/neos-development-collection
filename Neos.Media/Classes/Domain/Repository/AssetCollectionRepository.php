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
use Neos\Media\Domain\Model\AssetCollection;

/**
 * A repository for AssetCollections
 *
 * @method AssetCollection findOneByTitle(string $title)
 * @method QueryResultInterface<AssetCollection> findByParent(AssetCollection $parent)
 *
 * @Flow\Scope("singleton")
 */
class AssetCollectionRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = ['title' => QueryInterface::ORDER_ASCENDING];

    /**
     * Remove all child collections recursively to prevent orphaned collections
     */
    public function remove($object): void
    {
        /** @var AssetCollection $object */
        $childCollections = $this->findByParent($object);
        foreach ($childCollections as $childCollection) {
            $this->remove($childCollection);
        }
        parent::remove($object);
    }


}
