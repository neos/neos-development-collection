<?php
namespace Neos\Media\Security\Authorization\Privilege;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilege;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Security\Authorization\Privilege\Doctrine\AssetCollectionConditionGenerator;

/**
 * Privilege for restricting reading of AssetCollections
 */
class ReadAssetCollectionPrivilege extends EntityPrivilege
{
    /**
     * @param string $entityType
     * @return boolean
     */
    public function matchesEntityType($entityType)
    {
        return $entityType === AssetCollection::class;
    }

    /**
     * @return AssetCollectionConditionGenerator
     */
    protected function getConditionGenerator()
    {
        return new AssetCollectionConditionGenerator();
    }
}
