<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        */

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
    static protected $loggedDeprecation = false;

    /**
     * @param integer $width
     * @param integer $maximumWidth
     * @param integer $height
     * @param integer $maximumHeight
     * @param boolean $allowCropping
     * @param boolean $allowUpScaling
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
