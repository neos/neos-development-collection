<?php
namespace Neos\Media\Browser\AssetSource\Neos;

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

use Neos\Media\Browser\AssetSource\AssetProxyRepository;
use Neos\Media\Browser\AssetSource\AssetSource;
use Neos\Flow\Annotations\Proxy;

/**
 * @Proxy(false)
 */
final class NeosAssetSource implements AssetSource
{
    /**
     * @var string
     */
    private $assetSourceIdentifier;

    /**
     * @var NeosAssetProxyRepository
     */
    private $assetProxyRepository;

    /**
     * @param string $assetSourceIdentifier
     * @param array $assetSourceOptions
     */
    public function __construct(string $assetSourceIdentifier, array $assetSourceOptions)
    {
        if (preg_match('/^[a-z][a-z0-9-]{0,62}[a-z]$/', $assetSourceIdentifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid asset source identifier "%s". The identifier must match /^[a-z][a-z0-9-]{0,62}[a-z]$/', $assetSourceIdentifier), 1513329665386);
        }
        $this->assetSourceIdentifier = $assetSourceIdentifier;

        foreach ($assetSourceOptions as $optionName => $optionValue) {
            switch ($optionName) {
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown asset source option "%s" specified for Neos asset source "%s". Please check your settings.', $optionName, $assetSourceIdentifier), 1513327774584);
            }
        }
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->assetSourceIdentifier;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'Neos';
    }

    /**
     * @return AssetProxyRepository
     */
    public function getAssetProxyRepository(): AssetProxyRepository
    {
        if ($this->assetProxyRepository === null) {
            $this->assetProxyRepository = new NeosAssetProxyRepository($this);
        }

        return $this->assetProxyRepository;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return false;
    }
}
