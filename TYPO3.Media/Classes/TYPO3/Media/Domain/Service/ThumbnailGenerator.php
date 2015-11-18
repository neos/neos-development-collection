<?php
namespace TYPO3\Media\Domain\Service;

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
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Service\ThumbnailService;

/**
 * A thumbnail generation service.
 *
 * @Flow\Scope("singleton")
 */
class ThumbnailGenerator
{
    /**
     * @Flow\InjectConfiguration("autoGenerateThumbnailPresets")
     * @var boolean
     */
    protected $autoGenerateThumbnailPresets;

    /**
     * If enabled
     * @Flow\InjectConfiguration("asyncThumbnails")
     * @var boolean
     */
    protected $asyncThumbnails;

    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @param ImageInterface $image
     * @return void
     */
    public function generateThumbnails(ImageInterface $image)
    {
        if ($this->autoGenerateThumbnailPresets) {
            foreach ($this->thumbnailService->getPresets() as $preset => $presetConfiguration) {
                $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset);
                $this->thumbnailService->getThumbnail($image, $thumbnailConfiguration, $this->asyncThumbnails);
            }
        }
    }
}
