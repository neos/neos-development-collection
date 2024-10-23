<?php

namespace Neos\Media\Domain\Service\Imagor;

class ImagorPathBuilder
{
    private bool $trim = false;
    private ?string $crop = null;
    private bool $fitIn = false;
    private bool $stretch = false;
    private int $resizeWidth = 0;
    private int $resizeHeight = 0;
    private bool $flipHorizontally = false;
    private bool $flipVertically = false;
    private ?string $padding = null;
    private ?string $hAlign = null;
    private ?string $vAlign = null;
    private bool $smart = false;
    private array $filters = [];
    private ?string $secret = null;
    private ?string $signerType = null;
    private ?int $signerTruncate = null;

    /**
     * trim removes surrounding space in images using top-left pixel color
     *
     * @return $this
     */
    public function trim(): self
    {
        $this->trim = true;
        return $this;
    }

    /**
     * AxB:CxD means manually crop the image at left-top point AxB and right-bottom point CxD.
     * Coordinates can also be provided as float values between 0 and 1 (percentage of image dimensions)
     *
     * @param int $a top left x coordinate
     * @param int $b top left y coordinate
     * @param int $c bottom right x coordinate
     * @param int $d bottom right y coordinate
     * @return $this
     */
    public function crop(int $a, int $b, int $c, int $d): self
    {
        $this->crop = sprintf('%dx%d:%dx%d', $a, $b, $c, $d);
        return $this;
    }

    /**
     * fit-in means that the generated image should not be auto-cropped and
     * otherwise just fit in an imaginary box specified by ExF
     *
     * @return $this
     */
    public function fitIn(): self
    {
        $this->fitIn = true;
        return $this;
    }

    /**
     * stretch means resize the image to ExF without keeping its aspect ratios
     *
     * @return $this
     */
    public function stretch(): self
    {
        $this->stretch = true;
        return $this;
    }

    /**
     * ExF means resize the image to be ExF of width per height size.
     *
     * @param int $width
     * @param int $height
     * @return self
     */
    public function resize(int $width, int $height): self
    {
        $this->resizeWidth = $width;
        $this->resizeHeight = $height;
        return $this;
    }

    public function getResizeWidth(): int
    {
        return $this->resizeWidth;
    }

    public function getResizeHeight(): int
    {
        return $this->resizeHeight;
    }

    public function flipHorizontally(): self
    {
        $this->flipHorizontally = !$this->flipHorizontally;
        return $this;
    }

    public function flipVertically(): self
    {
        $this->flipVertically = !$this->flipVertically;
        return $this;
    }

    /**
     * GxH:IxJ add left-top padding GxH and right-bottom padding IxJ
     *
     * @param int $left
     * @param int $top
     * @param int $right
     * @param int $bottom
     * @return self
     */
    public function padding(int $left, int $top, int $right, int $bottom): self
    {
        $this->padding = sprintf('%dx%d:%dx%d', $left, $top, $right, $bottom);
        return $this;
    }

    /**
     * HALIGN is horizontal alignment of crop. Accepts left, right or center, defaults to center
     * @param string $hAlign
     * @return self
     */
    public function hAlign(string $hAlign): self
    {
        if (!in_array($hAlign, ['left', 'right', 'center'])) {
            throw new \RuntimeException('Unsupported hAlign: ' . $hAlign);
        }
        $this->hAlign = $hAlign;
        return $this;
    }

    /**
     * VALIGN is vertical alignment of crop. Accepts top, bottom or middle, defaults to middle
     * @param string $vAlign
     * @return self
     */
    public function vAlign(string $vAlign): self
    {
        if (!in_array($vAlign, ['top', 'bottom', 'middle'])) {
            throw new \RuntimeException('Unsupported vAlign: ' . $vAlign);
        }
        $this->vAlign = $vAlign;
        return $this;
    }

    /**
     * smart means using smart detection of focal points
     *
     * @return $this
     */
    public function smart(): self
    {
        $this->smart = true;
        return $this;
    }

    /**
     * @param string $filterName
     * @param mixed ...$args
     * @return $this
     */
    public function addFilter(string $filterName, ...$args): self
    {
        $this->filters[] = $filterName . '(' . implode(',', $args) . ')';
        return $this;
    }

    public function secret(?string $secret): self
    {
        $this->secret = $secret;
        return $this;
    }

    public function signerType(?string $signerType): self
    {
        $this->signerType = $signerType;
        return $this;
    }

    public function signerTruncate(?int $signerTruncate): self
    {
        $this->signerTruncate = $signerTruncate;
        return $this;
    }

    public function build(string $sourceImage): string
    {
        $decodedPathSegments = [];

        if ($this->trim) {
            $decodedPathSegments[] = 'trim';
        }
        if ($this->crop) {
            $decodedPathSegments[] = $this->crop;
        }
        if ($this->fitIn) {
            $decodedPathSegments[] = 'fit-in';
        }
        if ($this->stretch) {
            $decodedPathSegments[] = 'stretch';
        }
        if ($this->resizeWidth !== 0 || $this->resizeHeight !== 0 || $this->flipVertically || $this->flipHorizontally) {
            $decodedPathSegments[] = sprintf(
                '%s%dx%s%d',
                $this->flipVertically ? '-' : '',
                $this->resizeWidth,
                $this->flipHorizontally ? '-' : '',
                $this->resizeHeight
            );
        }
        if ($this->padding) {
            $decodedPathSegments[] = $this->padding;
        }
        if ($this->hAlign) {
            $decodedPathSegments[] = $this->hAlign;
        }
        if ($this->vAlign) {
            $decodedPathSegments[] = $this->vAlign;
        }
        if ($this->smart) {
            $decodedPathSegments[] = 'smart';
        }
        if (!empty($this->filters)) {
            $decodedPathSegments[] = 'filters:' . implode(':', $this->filters);
        }

        // eg example.net/kisten-trippel_3_kw%282%29.jpg
        $encodedSourcePath = ltrim($sourceImage, '/');
        // eg 30x40%3A100x150%2Ffilters%3Afill%28cyan%29
        $encodedPathSegments = array_map(function ($segment) {
            return urlencode($segment);
        }, $decodedPathSegments);
        $encodedPathSegments[] = $encodedSourcePath;
        // eg 30x40%3A100x150%2Ffilters%3Afill%28cyan%29/example.net/kisten-trippel_3_kw%282%29.jpg
        $encodedPath = implode('/', $encodedPathSegments);

        // eg example.net/kisten-trippel_3_kw(2).jpg
        $sourcePathDecoded = urldecode($encodedSourcePath);
        $decodedPathSegments[] = $sourcePathDecoded;
        // eg 30x40:100x150/filters:fill(cyan)/example.net/kisten-trippel_3_kw(2).jpg
        $decodedPath = implode('/', $decodedPathSegments);

        // eg N…mVw/30x40%3A100x150%2Ffilters%3Afill%28cyan%29/example.net/kisten-trippel_3_kw%282%29.jpg
        return $this->hmac($decodedPath) . "/" . $encodedPath;
    }

    private function hmac(string $path): string
    {
        if (empty($this->signerType) || empty($this->secret)) {
            return 'unsafe';
        } else {
            $hash = strtr(
                base64_encode(
                    hash_hmac(
                        $this->signerType,
                        $path,
                        $this->secret,
                        true
                    )
                ),
                '/+',
                '_-'
            );
            if ($this->signerTruncate === null) {
                return $hash;
            } else {
                return substr($hash, 0, $this->signerTruncate);
            }
        }
    }
}
