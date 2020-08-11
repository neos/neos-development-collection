<?php
declare(strict_types=1);

namespace Neos\Media\Domain\ValueObject\Configuration;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class AspectRatio
{
    public const ORIENTATION_LANDSCAPE = 'landscape';
    public const ORIENTATION_PORTRAIT = 'portrait';
    public const ORIENTATION_SQUARE = 'square';

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @param int $width
     * @param int $height
     */
    public function __construct(int $width, int $height)
    {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException('aspect ratio: width and height must be positive integers.', 1549455812);
        }

        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param string $ratio
     * @return self
     */
    public static function fromString(string $ratio): self
    {
        if (preg_match('/^\d+:\d+$/', $ratio) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid aspect ratio specified ("%s").', $ratio), 1552641724);
        }
        [$width, $height] = explode(':', $ratio);
        return new static((int)$width, (int)$height);
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return float
     */
    public function getRatio(): float
    {
        return $this->width / $this->height;
    }

    /**
     * @return string
     */
    public function getOrientation(): string
    {
        $ratio = $this->getRatio();
        if ($ratio === (float)1) {
            return self::ORIENTATION_SQUARE;
        }
        return $ratio > 1 ? self::ORIENTATION_LANDSCAPE : self::ORIENTATION_PORTRAIT;
    }

    /**
     * @return bool
     */
    public function isOrientationLandscape(): bool
    {
        return $this->getOrientation() === self::ORIENTATION_LANDSCAPE;
    }

    /**
     * @return bool
     */
    public function isOrientationPortrait(): bool
    {
        return $this->getOrientation() === self::ORIENTATION_PORTRAIT;
    }

    /**
     * @return bool
     */
    public function isOrientationSquare(): bool
    {
        return $this->getOrientation() === self::ORIENTATION_SQUARE;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->width . ':' . $this->height;
    }
}
