<?php

namespace Neos\Media\Browser;

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

use Neos\Media\Browser\AssetSource\ImportedAssetManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Media\Domain\Service\AssetService;

/**
 * The Media Package
 */
class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(AssetService::class, 'assetCreated', ImportedAssetManager::class, 'registerCreatedAsset');
        $dispatcher->connect(AssetService::class, 'assetRemoved', ImportedAssetManager::class, 'registerRemovedAsset');
    }
}
