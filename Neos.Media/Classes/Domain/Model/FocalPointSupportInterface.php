<?php
declare(strict_types=1);

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

use Imagine\Image\PointInterface;

/**
 * Interface for assets which provide methods for focal points
 */
interface FocalPointSupportInterface
{
    public function getFocalPointX(): ?int;

    public function setFocalPointX(?int $x): void;

    public function getFocalPointY(): ?int;

    public function setFocalPointY(?int $y): void;

    public function hasFocalPoint(): bool;

    public function getFocalPoint(): ?PointInterface;
}
