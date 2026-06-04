<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Model\Adjustment;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\Point;
use Neos\Flow\Annotations as Flow;
use Imagine\Image\Palette;

/**
 * An adjustment to draw on circle on an image
 * this is solely for debugging of focal points
 * @todo remove before merging
 *
 * @deprecated
 * @Flow\Entity
 */
class MarkPointAdjustment extends AbstractImageAdjustment
{
    protected $position = 99;

    protected ?int $x = null;

    protected ?int $y = null;

    protected int $radius;

    protected int $thickness = 1;

    protected string $color = '#000';


    public function setX(?int $x): void
    {
        $this->x = $x;
    }

    public function setY(?int $y): void
    {
        $this->y = $y;
    }

    public function setRadius(int $radius): void
    {
        $this->radius = $radius;
    }

    public function setThickness(int $thickness): void
    {
        $this->thickness = $thickness;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }


    public function applyToImage(ImagineImageInterface $image)
    {
        if ($this->x === null && $this->y === null) {
            return $image;
        }

        $palette = new Palette\RGB();
        $color = $palette->color($this->color);

        if ($this->x !== null && $this->y !== null) {
            $image->draw()
                  ->circle(
                      new Point($this->x, $this->y),
                      $this->radius,
                      $color,
                      false,
                      $this->thickness
                  );
        }

        if ($this->x !== null) {
            $image->draw()
                  ->line(
                      new Point($this->x, 0),
                      new Point($this->x, $image->getSize()->getHeight()),
                      $color,
                      1
                  );
        }

        if ($this->y !== null) {
            $image->draw()
                  ->line(
                      new Point(0, $this->y),
                      new Point($image->getSize()->getWidth(), $this->y),
                      $color,
                      1
                  );
        }

        return $image;
    }

    public function canBeApplied(ImagineImageInterface $image)
    {
        if (is_null($this->x) || is_null($this->y) || is_null($this->radius)) {
            return false;
        }
        return true;
    }
}
