<?php

namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A set of points in the dimension space.
 *
 * E.g.: {[language => es, country => ar], [language => es, country => es]}
 */
final class DimensionSpacePointSet implements \JsonSerializable
{

    /**
     * @var array<DimensionSpacePoint>
     */
    private $points;

    /**
     * @param array<DimensionSpacePoint> $points Array of dimension space points
     */
    public function __construct(array $points)
    {
        $this->points = $points;
    }

    /**
     * @return array
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    function jsonSerialize()
    {
        return ['points' => $this->points];
    }

    public function __toString()
    {
        return 'dimension space points:[' . implode(',', $this->points) . ']';
    }

}