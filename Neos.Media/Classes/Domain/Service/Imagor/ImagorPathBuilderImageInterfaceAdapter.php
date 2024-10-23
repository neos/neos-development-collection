<?php

namespace Neos\Media\Domain\Service\Imagor;

use Imagine\Image\BoxInterface;
use Imagine\Image\Fill\FillInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\PointInterface;
use Imagine\Image\ProfileInterface;
use Neos\Media\Imagine\Box;

class ImagorPathBuilderImageInterfaceAdapter implements ImageInterface
{
    private readonly ImagorPathBuilder $builder;
    private ?int $width;
    private ?int $height;

    /**
     * @param ImagorPathBuilder $builder
     * @param int|null $width
     * @param int|null $height
     */
    public function __construct(ImagorPathBuilder $builder, ?int $width, ?int $height)
    {
        $this->builder = $builder;
        $this->width = $width;
        $this->height = $height;
    }

    public function copy()
    {
        return $this;
    }

    public function crop(PointInterface $start, BoxInterface $size)
    {
        $this->builder->crop(
            $start->getX(),
            $start->getY(),
            $start->getX() + $size->getWidth(),
            $start->getY() + $size->getHeight()
        );
        return $this;
    }

    public function resize(BoxInterface $size, $filter = ImageInterface::FILTER_UNDEFINED)
    {
        $this->builder->resize(
            $size->getWidth(),
            $size->getHeight()
        );
        return $this;
    }

    public function rotate($angle, ColorInterface $background = null)
    {
        $this->builder->addFilter('rotate', $angle);
        return $this;
    }

    public function paste(ImageInterface $image, PointInterface $start, $alpha = 100)
    {
        throw new NotSupportedByImagor();
    }

    public function save($path = null, array $options = array())
    {
        return $this;
    }

    public function show($format, array $options = array())
    {
        return $this;
    }

    public function flipHorizontally()
    {
        $this->builder->flipHorizontally();
        return $this;
    }

    public function flipVertically()
    {
        $this->builder->flipVertically();
        return $this;
    }

    public function strip()
    {
        $this->builder->addFilter('strip_exif');
        $this->builder->addFilter('strip_icc');
        return $this;
    }

    public function thumbnail(
        BoxInterface $size,
        $settings = self::THUMBNAIL_INSET,
        $filter = ImageInterface::FILTER_UNDEFINED
    ) {
        $this->resize($size);
        return $this;
    }

    public function applyMask(ImageInterface $mask)
    {
        throw new NotSupportedByImagor();
    }

    public function fill(FillInterface $fill)
    {
        throw new NotSupportedByImagor();
    }

    public function get($format, array $options = array())
    {
        throw new NotSupportedByImagor();
    }

    public function draw()
    {
        throw new NotSupportedByImagor();
    }

    public function effects()
    {
        throw new NotSupportedByImagor();
    }

    public function getSize(): BoxInterface
    {
        if ($this->width === null || $this->height === null) {
            // If this happens somewhere in the image processing
            // this exceptions is caught in the ImagorRendererImplementation (or earlier)
            // and resulting in the URI to be ''.
            throw new NotSupportedByImagor();
        } else {
            return new Box($this->width, $this->height);
        }
    }

    public function mask()
    {
        throw new NotSupportedByImagor();
    }

    public function histogram()
    {
        throw new NotSupportedByImagor();
    }

    public function getColorAt(PointInterface $point)
    {
        throw new NotSupportedByImagor();
    }

    public function layers()
    {
        throw new NotSupportedByImagor();
    }

    public function interlace($scheme)
    {
        throw new NotSupportedByImagor();
    }

    public function palette()
    {
        throw new NotSupportedByImagor();
    }

    public function usePalette(PaletteInterface $palette)
    {
        throw new NotSupportedByImagor();
    }

    public function profile(ProfileInterface $profile)
    {
        throw new NotSupportedByImagor();
    }

    public function metadata()
    {
        throw new NotSupportedByImagor();
    }

    public function __toString(): string
    {
        return "ImagorAdapter";
    }
}
