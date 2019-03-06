<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Model\ThumbnailGenerator;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\Thumbnail;

/**
 * A system-generated preview version of an Image
 */
class ConvertImageThumbnailGenerator extends ImageThumbnailGenerator
{
    /**
     * The priority for this thumbnail generator.
     *
     * @var integer
     * @api
     */
    protected static $priority = 50;

    /**
     * @param Thumbnail $thumbnail
     * @return boolean true if this ThumbnailGenerator can convert the given thumbnail, false otherwise.
     */
    public function canRefresh(Thumbnail $thumbnail)
    {
        return (
            $thumbnail->getOriginalAsset() instanceof ImageInterface &&
            $this->isExtensionSupported($thumbnail)
        );
    }

    /**
     * @param Thumbnail $thumbnail
     * @return string|null
     */
    protected function getTargetFormat(Thumbnail $thumbnail): ?string
    {
        return $thumbnail->getConfigurationValue('format') ?: $this->getOption('targetExtension');
    }

    /**
     * @param Thumbnail $thumbnail
     * @return boolean
     */
    protected function isExtensionSupported(Thumbnail $thumbnail)
    {
        $extension = $thumbnail->getOriginalAsset()->getResource()->getFileExtension();
        return in_array($extension, $this->getOption('supportedExtensions'));
    }
}
