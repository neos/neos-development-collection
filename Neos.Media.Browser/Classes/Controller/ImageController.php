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
use Neos\Media\Domain\Model\ImageVariant;
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
