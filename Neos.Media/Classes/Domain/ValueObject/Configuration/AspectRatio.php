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
        return new self((int)$width, (int)$height);
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
        return $this->width > $this->height ? $this->width / $this->height : $this->height / $this->width;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->width . ':' . $this->height;
    }
}
