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

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for methods regarding the quality of an asset
 */
trait QualityTrait
{
    /**
     * @var integer
     * @ORM\Column(nullable=true)
     */
    protected $quality = null;

    /**
     * Returns the quality of the image
     *
     * @return integer
     */
    public function getQuality()
    {
        return $this->quality;
    }
}
