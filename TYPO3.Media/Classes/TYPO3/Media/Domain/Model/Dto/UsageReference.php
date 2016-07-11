<?php
namespace TYPO3\Media\Domain\Model\Dto;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Media\Domain\Model\AssetInterface;

/**
 * A DTO for storing information related to a usage of an asset.
 */
class UsageReference
{

    /**
     * @var AssetInterface
     */
    protected $asset;

    /**
     * @param AssetInterface $asset
     */
    public function __construct(AssetInterface $asset)
    {
        $this->asset = $asset;
    }

    /**
     * @return AssetInterface
     */
    public function getAsset()
    {
        return $this->asset;
    }
}
