<?php
namespace TYPO3\Media\Domain\Model\ThumbnailGenerator;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Service\FileTypeIconService;
use TYPO3\Media\Domain\Service\ImageService;
use TYPO3\Media\Exception;

/**
 * A generic thumbnail generator to get Icon of the given document
 */
class IconThumbnailGenerator extends AbstractThumbnailGenerator
{
    /**
     * The priority for this thumbnail generator.
     *
     * @var integer
     * @api
     */
    protected static $priority = 1;

    /**
     * @var ImageService
     * @Flow\Inject
     */
    protected $imageService;

    /**
     * @param Thumbnail $thumbnail
     * @return void
     * @throws Exception\NoThumbnailAvailableException
     */
    public function refresh(Thumbnail $thumbnail)
    {
        try {
            $width = $thumbnail->getConfigurationValue('width') ?: $thumbnail->getConfigurationValue('maximumWidth');
            $height = $thumbnail->getConfigurationValue('height') ?: $thumbnail->getConfigurationValue('maximumHeight');

            /** @var AssetInterface $asset */
            $asset = $thumbnail->getOriginalAsset();
            $icon = FileTypeIconService::getIcon($asset, $width, $height);
            $thumbnail->setStaticResource($icon['src']);
            $thumbnail->setWidth($icon['width']);
            $thumbnail->setHeight($icon['height']);
        } catch (\Exception $exception) {
            $message = sprintf('Unable to generate thumbnail for the given image (filename: %s, SHA1: %s)', $thumbnail->getOriginalAsset()->getResource()->getFilename(), $thumbnail->getOriginalAsset()->getResource()->getSha1());
            throw new Exception\NoThumbnailAvailableException($message, 1433109654, $exception);
        }
    }
}
