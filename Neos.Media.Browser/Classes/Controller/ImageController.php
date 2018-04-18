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
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetSource\AssetSourceAwareInterface;
use Neos\Media\Domain\Model\ImportedAsset;
use Neos\Media\Domain\Repository\ImageRepository;

/**
 * Controller for browsing images in the ImageEditor
 */
class ImageController extends AssetController
{
    /**
     * @Flow\Inject
     * @var ImageRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var \Neos\Media\Domain\Repository\ImportedAssetRepository
     */
    protected $importedAssetRepository;

    /**
     * @param string $assetSourceIdentifier
     * @param string $assetProxyIdentifier
     * @param Asset $asset
     * @return void|string
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function editAction(string $assetSourceIdentifier = null, string $assetProxyIdentifier = null, Asset $asset = null)
    {
        if ($assetSourceIdentifier !== null && $assetProxyIdentifier !== null) {
            parent::editAction($assetSourceIdentifier, $assetProxyIdentifier);
            return;
        } elseif ($asset instanceof AssetSourceAwareInterface) {
            /** @var ImportedAsset $importedAsset */
            $importedAsset = $this->importedAssetRepository->findOneByLocalAssetIdentifier($asset->getIdentifier());
            parent::editAction($asset->getAssetSourceIdentifier(), $importedAsset ? $importedAsset->getRemoteAssetIdentifier() : $asset->getIdentifier());
            return;
        }
        $this->response->setStatus(400, 'Invalid arguments');
    }
}
