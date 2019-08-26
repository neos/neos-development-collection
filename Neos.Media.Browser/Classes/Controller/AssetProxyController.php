<?php
namespace Neos\Media\Browser\Controller;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Media\Domain\Service\AssetSourceService;
use Neos\Media\Exception\AssetSourceServiceException;

/**
 * @Flow\Scope("singleton")
 */
class AssetProxyController extends ActionController
{
    /**
     * @Flow\Inject
     * @var AssetSourceService
     */
    protected $assetSourceService;

    /**
     * Import a specified asset from the given Asset Source
     *
     * @param string $assetSourceIdentifier Identifier of the asset source to import from, e.g. "neos"
     * @param string $assetIdentifier The asset-source specific identifier of the asset to import
     * @return string
     */
    public function importAction(string $assetSourceIdentifier, string $assetIdentifier): string
    {
        $this->response->setContentType('application/json');

        try {
            $importedAsset = $this->assetSourceService->importAsset($assetSourceIdentifier, $assetIdentifier);
            $assetProxy = new \stdClass();
            $assetProxy->localAssetIdentifier = $importedAsset->getLocalAssetIdentifier();
            return json_encode($assetProxy);
        } catch (AssetSourceServiceException | \Exception $exception) {
            $this->response->setStatusCode(500);
            return $exception->getMessage();
        }
    }
}
