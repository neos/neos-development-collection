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

/**
 * A Video asset
 *
 * @Flow\Entity
 */
class Video extends Asset
{
    /**
     * @var integer
     * @Flow\Validate(type="NotEmpty")
     */
    protected $width;

    /**
     * @var integer
     * @Flow\Validate(type="NotEmpty")
     */
    protected $height;

    /**
     * Constructs the object and sets default values for width and height
     */
    public function __construct()
    {
        parent::__construct();

        $this->width = -1;
        $this->height = -1;
    }

    /**
     * Width of the video in pixels. If the width cannot be determined,
     * -1 is returned.
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Height of the video in pixels. If the height cannot be determined,
     * -1 is returned.
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }
}
