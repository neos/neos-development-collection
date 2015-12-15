<?php
namespace TYPO3\Media\Domain\Model;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

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
     * @Flow\InjectConfiguration("behaviourFlag")
     * @var string
     */
    protected $behaviourFlag;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $logger;

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
     */
    public function __construct($width = null, $maximumWidth = null, $height = null, $maximumHeight = null, $allowCropping = false, $allowUpScaling = false)
    {
        $this->width = $width ? (integer)$width : null;
        $this->maximumWidth = $maximumWidth ? (integer)$maximumWidth : null;
        $this->height = $height ? (integer)$height : null;
        $this->maximumHeight = $maximumHeight ? (integer)$maximumHeight : null;
        $this->allowCropping = $allowCropping ? (boolean)$allowCropping : false;
        $this->allowUpScaling = $allowUpScaling ? (boolean)$allowUpScaling : false;
    }

    /**
     * @return integer
     */
    public function getWidth()
    {
        if ($this->width !== null) {
            return $this->width;
        }
        if ($this->behaviourFlag === '1.2') {
            // @deprecated since 2.0, simulate the behaviour of 1.2
            if ($this->height === null && $this->isCroppingAllowed() && $this->getMaximumWidth() !== null && $this->getMaximumHeight() !== null) {
                $this->logDeprecation();
                return $this->getMaximumWidth();
            }
        }
        return null;
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
        if ($this->height !== null) {
            return $this->height;
        }
        if ($this->behaviourFlag === '1.2') {
            // @deprecated since 2.0, simulate the behaviour of 1.2
            if ($this->width === null && $this->isCroppingAllowed() && $this->getMaximumWidth() !== null && $this->getMaximumHeight() !== null) {
                $this->logDeprecation();
                return $this->getMaximumHeight();
            }
        }
        return null;
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
     * @return string
     */
    public function getHash()
    {
        return md5(json_encode($this->toArray()));
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
            'allowUpScaling' => $this->isUpScalingAllowed()
        ], function ($value) {
            return $value !== null;
        });
        ksort($data);
        return $data;
    }

    /**
     * Log a deprecation message once
     *
     * @return void
     */
    protected function logDeprecation()
    {
        if (!static::$loggedDeprecation) {
            static::$loggedDeprecation = true;
            $this->logger->log('TYPO3.Media is configured to simulate the deprecated Neos 1.2 behaviour. Please check the setting "TYPO3.Media.behaviourFlag".',
                LOG_DEBUG);
        }
    }
}
