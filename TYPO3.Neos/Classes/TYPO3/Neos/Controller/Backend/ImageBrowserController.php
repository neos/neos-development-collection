<?php
namespace TYPO3\Neos\Controller\Backend;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Repository\ImageRepository;

/**
 * Controller for browsing images in the ImageEditor
 */
class ImageBrowserController extends MediaBrowserController
{
    /**
     * @Flow\Inject
     * @var ImageRepository
     */
    protected $assetRepository;

    /**
     * @param Asset $asset
     * @return void
     */
    public function editAction(Asset $asset)
    {
        if ($asset instanceof ImageVariant) {
            $asset = $asset->getOriginalAsset();
        }
        parent::editAction($asset);
    }
}
