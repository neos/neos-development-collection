<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Model\Dto;

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
use Imagine\Image\PointInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A DTO for storing the information for a preliminary in case a focal point is respected during thumbnail generation
 * plus the position of the focal point in the image after cropping and resizing
 *
 * @internal
 */
#[Flow\Proxy(false)]
final readonly class PreliminaryCropSpecification
{
    public function __construct(
        public PointInterface $cropOffset,
        public BoxInterface $cropDimensions,
        public PointInterface $focalPoint,
    ) {
    }
}
