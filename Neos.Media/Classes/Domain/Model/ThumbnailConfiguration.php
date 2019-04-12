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
 * Thumbnail configuration value object
 */
class ThumbnailConfiguration
{
    /**
     * @var integer
     */
    protected $width;

    /**
     * @var integer
     */
    protected $maximumWidth;

    /**
     * @var integer
     */
    protected $height;

    /**
     * @var integer
     */
    protected $maximumHeight;

    /**
     * @var boolean
     */
    protected $allowCropping;

    /**
     * @var boolean
     */
    protected $allowUpScaling;

    /**
     * @var integer
     */
    protected $quality;

    /**
     * @var boolean
     */
    protected $async;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var boolean
     */
    protected static $loggedDeprecation = false;

    /**
     * @param integer $width Desired width of the image
     * @param integer $maximumWidth Desired maximum width of the image
     * @param integer $height Desired height of the image
     * @param integer $maximumHeight Desired maximum height of the image
     * @param boolean $allowCropping Whether the image should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting image size might exceed the size of the original image
     * @param boolean $async Whether the thumbnail can be generated asynchronously
     * @param integer $quality Quality of the processed image
     * @param string $format Format for the image, only jpg, jpeg, gif, png, wbmp, xbm, webp and bmp are supported.
     */
    public function __construct($width = null, $maximumWidth = null, $height = null, $maximumHeight = null, $allowCropping = false, $allowUpScaling = false, $async = false, $quality = null, $format = null)
    {
        $this->width = $width ? (integer)$width : null;
        $this->maximumWidth = $maximumWidth ? (integer)$maximumWidth : null;
        $this->height = $height ? (integer)$height : null;
        $this->maximumHeight = $maximumHeight ? (integer)$maximumHeight : null;
        $this->allowCropping = $allowCropping ? (boolean)$allowCropping : false;
        $this->allowUpScaling = $allowUpScaling ? (boolean)$allowUpScaling : false;
        $this->async = $async ? (boolean)$async : false;
        $this->quality = $quality ? (integer)$quality : null;
        $this->format = $format ? (string)$format : null;
    }

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return integer
     */
    public function getMaximumWidth()
    {
        return $this->maximumWidth;
    }

    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return integer
     */
    public function getMaximumHeight()
    {
        return $this->maximumHeight;
    }

    /**
     * @return boolean
     */
    public function getRatioMode()
    {
        return ($this->isCroppingAllowed() ? ImageInterface::RATIOMODE_OUTBOUND : ImageInterface::RATIOMODE_INSET);
    }

    /**
     * @return boolean
     */
    public function isCroppingAllowed()
    {
        return $this->allowCropping;
    }

    /**
     * @return boolean
     */
    public function isUpScalingAllowed()
    {
        return $this->allowUpScaling;
    }

    /**
     * @return boolean
     */
    public function isAsync()
    {
        return $this->async;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return md5(json_encode($this->toArray()));
    }

    /**
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = array_filter([
            'width' => $this->getWidth(),
            'maximumWidth' => $this->getMaximumWidth(),
            'height' => $this->getHeight(),
            'maximumHeight' => $this->getMaximumHeight(),
            'ratioMode' => $this->getRatioMode(),
            'allowUpScaling' => $this->isUpScalingAllowed(),
            'quality' => $this->getQuality(),
            'format' => $this->getFormat()
        ], function ($value) {
            return $value !== null;
        });
        ksort($data);
        return $data;
    }
}
