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
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;

/**
 * A repository for Asset Sources
 *
 * @Flow\Scope("singleton")
 */
class AssetSourceRepository
{
    /**
     * @Flow\InjectConfiguration(path="assetSources")
     * @var array
     */
    protected $assetSourcesConfiguration;

    /**
     * @var AssetSourceInterface[]
     */
    protected $assetSources = [];

    /**
     * @return AssetSourceInterface[]
     */
    public function findAll(): array
    {
        $this->initialize();
        return $this->assetSources;
    }

    /**
     * @return void
     */
    private function initialize()
    {
        if ($this->assetSources === []) {
            foreach ($this->assetSourcesConfiguration as $assetSourceIdentifier => $assetSourceConfiguration) {
                if (is_array($assetSourceConfiguration)) {
                    $this->assetSources[$assetSourceIdentifier] = new $assetSourceConfiguration['assetSource']($assetSourceIdentifier, $assetSourceConfiguration['assetSourceOptions'] ?? []);
                }
            }
        }
    }

}
