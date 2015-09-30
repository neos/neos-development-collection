<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
