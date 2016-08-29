<?php
namespace TYPO3\Media\Domain\Strategy;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\AssetInterface;

/**
 * Abstract asset usage strategy
 *
 * @Flow\Scope("singleton")
 */
abstract class AbstractAssetUsageStrategy implements AssetUsageStrategyInterface
{

    /**
     * Returns true if the asset is used.
     *
     * @param AssetInterface $asset
     * @return boolean
     */
    public function isInUse(AssetInterface $asset)
    {
        return $this->getUsageReferences($asset) !== [];
    }

    /**
     * Returns the total count of usages found.
     *
     * @param AssetInterface $asset
     * @return integer
     */
    public function getUsageCount(AssetInterface $asset)
    {
        return count($this->getUsageReferences($asset));
    }
}
