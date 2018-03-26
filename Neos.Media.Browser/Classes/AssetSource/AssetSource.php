<?php
namespace Neos\Media\Browser\AssetSource;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 * (c) Robert Lemke, Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

interface AssetSource
{
    /**
     * @param string $assetSourceIdentifier
     * @param array $assetSourceOptions
     */
    public function __construct(string $assetSourceIdentifier, array $assetSourceOptions);

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
     * @return AssetProxyRepository
     */
    public function getAssetProxyRepository(): AssetProxyRepository;

    /**
     * @return bool
     */
    public function isReadOnly(): bool;
}
