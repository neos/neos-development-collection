<?php
namespace Neos\Media\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Interface of an Image
 */
interface ImageInterface extends ResourceBasedInterface
{
    const ORIENTATION_SQUARE = 'square';
    const ORIENTATION_LANDSCAPE = 'landscape';
    const ORIENTATION_PORTRAIT = 'portrait';

    /**
     * Inset ratio mode: If an image is attempted to get scaled with the size of both edges stated, using this mode will scale it to the lower of both edges.
     * Consider an image of 320/480 being scaled to 50/50: because aspect ratio wouldn't get hurt, the target image size will become 33/50.
     */
    const RATIOMODE_INSET = 'inset';

    /**
     * Outbound ratio mode: If an image is attempted to get scaled with the size of both edges stated, using this mode will scale the image and crop it.
     * Consider an image of 320/480 being scaled to 50/50: the image will be scaled to height 50, then centered and cropped so the width will also be 50.
     */
    const RATIOMODE_OUTBOUND = 'outbound';

    /**
     * Width of the image in pixels
     *
     * @return integer
     */
    public function getWidth();

    /**
     * Height of the image in pixels
     *
     * @return integer
     */
    public function getHeight();

    /**
     * Edge / aspect ratio of the image
     *
     * @param boolean $respectOrientation If false (the default), orientation is disregarded and always a value >= 1 is returned (like usual in "4 / 3" or "16 / 9")
     * @return float
     */
    public function getAspectRatio($respectOrientation = false);

    /**
     * Orientation of this image, i.e. portrait, landscape or square
     *
     * @return string One of this interface's ORIENTATION_* constants.
     */
    public function getOrientation();

    /**
     * Whether this image is square aspect ratio and therefore has a square orientation
     *
     * @return boolean
     */
    public function isOrientationSquare();

    /**
     * Whether this image is in landscape orientation
     *
     * @return boolean
     */
    public function isOrientationLandscape();

    /**
     * Whether this image is in portrait orientation
     *
     * @return boolean
     */
    public function isOrientationPortrait();
}
