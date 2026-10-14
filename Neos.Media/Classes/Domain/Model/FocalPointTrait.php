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
use Imagine\Image\Point;
use Imagine\Image\PointInterface;

/**
 * Trait for assets which provide methods for focal points
 * @see FocalPointSupportInterface
 */
trait FocalPointTrait
{
    /**
     * @var integer
     * @ORM\Column(nullable=true)
     */
    protected $focalPointX = null;

    /**
     * @var integer
     * @ORM\Column(nullable=true)
     */
    protected $focalPointY = null;

    public function getFocalPointX(): ?int
    {
        return $this->focalPointX;
    }

    public function setFocalPointX(?int $x): void
    {
        $this->focalPointX = $x;
    }

    public function getFocalPointY(): ?int
    {
        return $this->focalPointY;
    }

    public function setFocalPointY(?int $y): void
    {
        $this->focalPointY = $y;
    }

    public function hasFocalPoint(): bool
    {
        if ($this->focalPointX !== null && $this->focalPointY !== null) {
            return true;
        }
        return false;
    }

    public function getFocalPoint(): ?PointInterface
    {
        if ($this->hasFocalPoint()) {
            return new Point($this->focalPointX, $this->focalPointY);
        }
        return null;
    }
}
