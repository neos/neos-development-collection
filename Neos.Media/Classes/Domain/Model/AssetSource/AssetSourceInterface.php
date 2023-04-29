<?php
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

interface AssetSourceInterface
{
    /**
     * This factory method is used instead of a constructor in order to not dictate a __construct() signature in this
     * interface (which might conflict with an asset source's implementation or generated Flow proxy class).
     *
     * @param string $assetSourceIdentifier
     * @param array $assetSourceOptions
     * @return AssetSourceInterface
     */
    public static function createFromConfiguration(string $assetSourceIdentifier, array $assetSourceOptions): AssetSourceInterface;

    /**
     * A unique string which identifies the concrete asset source.
     * Must match /^[a-z][a-z0-9-]{0,62}[a-z]$/
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * Returns the resource path to the icon of the asset source
     *
     * @return string
     */
    public function getIconUri(): string;

    /**
     * Returns the description of the asset source
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return AssetProxyRepositoryInterface
     */
    public function getAssetProxyRepository(): AssetProxyRepositoryInterface;

    /**
     * @return bool
     */
    public function isReadOnly(): bool;
}
