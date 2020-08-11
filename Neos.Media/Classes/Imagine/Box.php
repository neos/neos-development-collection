<?php
namespace Neos\Media\Imagine;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use Imagine\Image\PointInterface;

class Box implements BoxInterface
{

    /**
     * @var integer
     */
    private $width;

    /**
     * @var integer
     */
    private $height;

    /**
     * Constructs the Size with given width and height
     *
     * @param integer $width
     * @param integer $height
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($width, $height)
    {
        if ($height < 1 || $width < 1) {
            throw new \InvalidArgumentException(sprintf(
                'Length of either side cannot be 0 or negative, current size is %sx%s',
                $width,
                $height
            ), 1465382619);
        }

        $this->width = (integer)$width;
        $this->height = (integer)$height;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * {@inheritdoc}
     */
    public function scale($ratio)
    {
        return new static(max(round($ratio * $this->width), 1), max(round($ratio * $this->height), 1));
    }

    /**
     * {@inheritdoc}
     */
    public function increase($size)
    {
        return new static((integer)$size + $this->width, (integer)$size + $this->height);
    }

    /**
     * {@inheritdoc}
     */
    public function contains(BoxInterface $box, PointInterface $start = null)
    {
        $start = $start ? $start : new Point(0, 0);

        return $start->in($this) && $this->width >= $box->getWidth() + $start->getX() && $this->height >= $box->getHeight() + $start->getY();
    }

    /**
     * {@inheritdoc}
     */
    public function square()
    {
        return $this->width * $this->height;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf('%dx%d px', $this->width, $this->height);
    }

    /**
     * {@inheritdoc}
     */
    public function widen($width)
    {
        return $this->scale($width / $this->width);
    }

    /**
     * {@inheritdoc}
     */
    public function heighten($height)
    {
        return $this->scale($height / $this->height);
    }
}
