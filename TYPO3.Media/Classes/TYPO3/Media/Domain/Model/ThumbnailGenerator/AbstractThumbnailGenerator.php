<?php
namespace TYPO3\Media\Domain\Model\ThumbnailGenerator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Imagine\Image\ImagineInterface;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Exception;

/**
 * Abstract Thumbnail Generator
 */
abstract class AbstractThumbnailGenerator implements ThumbnailGeneratorInterface
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Utility\Environment
     */
    protected $environment;

    /**
     * @var ImagineInterface
     * @Flow\Inject(lazy = false)
     */
    protected $imagineService;

    /**
     * @var ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

    /**
     * The priority for this thumbnail generator.
     *
     * @var integer
     * @api
     */
    protected $priority;

    /**
     * @var array
     */
    protected $supportedExtensions = array();

    /**
     * @var string
     */
    protected $currentExtension;

    /**
     * @Flow\InjectConfiguration(path="image.defaultOptions")
     * @var array
     */
    protected $options;

    /**
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param Thumbnail $thumbnail
     * @return boolean TRUE if this ThumbnailGenerator can convert the given thumbnail, FALSE otherwise.
     * @api
     */
    public function canRefresh(Thumbnail $thumbnail)
    {
        return true;
    }

    /**
     * @param Thumbnail $thumbnail
     * @return boolean
     */
    protected function isExtensionSupported(Thumbnail $thumbnail)
    {
        $this->currentExtension = $thumbnail->getOriginalAsset()->getResource()->getFileExtension();
        return in_array($this->currentExtension, $this->supportedExtensions);
    }

    /**
     * @param array $files
     */
    protected function unlinkTemporaryFiles(array $files)
    {
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            unlink($file);
        }
    }
}
