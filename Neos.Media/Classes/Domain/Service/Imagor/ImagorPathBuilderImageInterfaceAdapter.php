<?php

declare(strict_types=1);

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
    private ImagorPathBuilder $builder;
    private int $width;
    private int $height;

    public function __construct(ImagorPathBuilder $builder, int $width, int $height)
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
        throw new NotSupportedByImagorException();
    }

    public function save($path = null, array $options = [])
    {
        return $this;
    }

    public function show($format, array $options = [])
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
        throw new NotSupportedByImagorException();
    }

    public function fill(FillInterface $fill)
    {
        throw new NotSupportedByImagorException();
    }

    public function get($format, array $options = [])
    {
        throw new NotSupportedByImagorException();
    }

    public function draw()
    {
        throw new NotSupportedByImagorException();
    }

    public function effects()
    {
        throw new NotSupportedByImagorException();
    }

    public function getSize(): BoxInterface
    {
        return new Box($this->width, $this->height);
    }

    public function mask()
    {
        throw new NotSupportedByImagorException();
    }

    public function histogram()
    {
        throw new NotSupportedByImagorException();
    }

    public function getColorAt(PointInterface $point)
    {
        throw new NotSupportedByImagorException();
    }

    public function layers()
    {
        throw new NotSupportedByImagorException();
    }

    public function interlace($scheme)
    {
        throw new NotSupportedByImagorException();
    }

    public function palette()
    {
        throw new NotSupportedByImagorException();
    }

    public function usePalette(PaletteInterface $palette)
    {
        throw new NotSupportedByImagorException();
    }

    public function profile(ProfileInterface $profile)
    {
        throw new NotSupportedByImagorException();
    }

    public function metadata()
    {
        throw new NotSupportedByImagorException();
    }

    public function __toString(): string
    {
        return "ImagorAdapter";
    }
}
