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
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
final class ImportedAssetRepository extends Repository
{
    /**
     * @param string $assetSourceIdentifier
     * @param string $remoteAssetIdentifier
     * @return object
     */
    public function findOneByAssetSourceIdentifierAndRemoteAssetIdentifier(string $assetSourceIdentifier, string $remoteAssetIdentifier)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd([
                $query->equals('assetSourceIdentifier', $assetSourceIdentifier),
                $query->equals('remoteAssetIdentifier', $remoteAssetIdentifier),
                $query->equals('localOriginalAssetIdentifier', null)
            ])
        );
        return $query->execute()->getFirst();
    }

    /**
     * @param string $localAssetIdentifier
     * @return object
     */
    public function findOneByLocalAssetIdentifier(string $localAssetIdentifier)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('localAssetIdentifier', $localAssetIdentifier)
        );
        return $query->execute()->getFirst();
    }
}
