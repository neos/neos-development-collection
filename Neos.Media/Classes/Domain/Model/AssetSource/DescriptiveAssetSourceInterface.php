<?php
declare(strict_types=1);
namespace Neos\Media\Domain\Model\AssetSource;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * @deprecated Will be integrated to the AssetSourceInterface with Neos 6.0
 */
interface DescriptiveAssetSourceInterface
{
    /**
     * Returns the resource path to Assetsources icon
     *
     * @return string
     */
    public function getIconUri(): string;

    /**
     * @return string
     */
    public function getDescription(): string;
}
