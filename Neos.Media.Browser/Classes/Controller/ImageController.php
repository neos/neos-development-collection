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
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceAwareInterface;
use Neos\Media\Domain\Model\ImportedAsset;
use Neos\Media\Domain\Model\Tag;
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
     * List existing images
     *
     * @param string $view
     * @param string $sortBy
     * @param string $sortDirection
     * @param string $filter
     * @param int $tagMode
     * @param Tag $tag
     * @param string $searchTerm
     * @param int $collectionMode
     * @param AssetCollection $assetCollection
     * @param string $assetSourceIdentifier
     * @return void
     * @throws \Neos\Utility\Exception\FilesException
     */
    public function indexAction($view = null, $sortBy = null, $sortDirection = null, $filter = null, $tagMode = self::TAG_GIVEN, Tag $tag = null, $searchTerm = null, $collectionMode = self::COLLECTION_GIVEN, AssetCollection $assetCollection = null, $assetSourceIdentifier = null): void
    {
        $this->view->assign('filterOptions', []);
        parent::indexAction($view, $sortBy, $sortDirection, 'Image', $tagMode, $tag, $searchTerm, $collectionMode, $assetCollection, $assetSourceIdentifier);
    }

    /**
     * @param string $assetSourceIdentifier
     * @param string $assetProxyIdentifier
     * @param AssetInterface $asset
     * @return void
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function editAction(string $assetSourceIdentifier = null, string $assetProxyIdentifier = null, AssetInterface $asset = null): void
    {
        if ($assetSourceIdentifier !== null && $assetProxyIdentifier !== null) {
            parent::editAction($assetSourceIdentifier, $assetProxyIdentifier);
            return;
        }

        if ($asset instanceof AssetSourceAwareInterface) {
            /** @var ImportedAsset $importedAsset */
            $importedAsset = $this->importedAssetRepository->findOneByLocalAssetIdentifier($asset->getIdentifier());
            parent::editAction($asset->getAssetSourceIdentifier(), $importedAsset ? $importedAsset->getRemoteAssetIdentifier() : $asset->getIdentifier());
            return;
        }
        $this->response->setStatusCode(400);
    }
}
