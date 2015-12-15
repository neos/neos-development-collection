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
use TYPO3\Media\Domain\Service\ImageService;
use TYPO3\Media\Exception;

/**
 * A generic thumbnail generator to get Icon of the given document
 */
class SvgThumbnailGenerator extends AbstractThumbnailGenerator
{
    /**
     * The priority for this thumbnail generator.
     *
     * @var integer
     * @api
     */
    protected static $priority = 10;

    /**
     * @var ImageService
     * @Flow\Inject
     */
    protected $imageService;

    /**
     * @param Thumbnail $thumbnail
     * @return boolean TRUE if this ThumbnailGenerator can convert the given thumbnail, FALSE otherwise.
     * @api
     */
    public function canRefresh(Thumbnail $thumbnail)
    {
        return $thumbnail->getOriginalAsset()->getResource()->getMediaType() === 'image/svg+xml';
    }

    /**
     * @param Thumbnail $thumbnail
     * @return void
     * @throws Exception\NoThumbnailAvailableException
     */
    public function refresh(Thumbnail $thumbnail)
    {
        try {
            /** @var AssetInterface $asset */
            $asset = $thumbnail->getOriginalAsset();
            $thumbnail->setStaticResource($this->resourceManager->getPublicPersistentResourceUri($asset->getResource()));
        } catch (\Exception $exception) {
            $filename = $thumbnail->getOriginalAsset()->getResource()->getFilename();
            $sha1 = $thumbnail->getOriginalAsset()->getResource()->getSha1();
            $message = sprintf('Unable to generate thumbnail for the given SVG (filename: %s, SHA1: %s)', $filename, $sha1);
            throw new Exception\NoThumbnailAvailableException($message, 1433109654, $exception);
        }
    }
}
