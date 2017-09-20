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
 * A point in the dimension space.
 *
 * E.g.: [language => es, country => ar]
 */
final class DimensionSpacePoint implements \JsonSerializable
{

    /**
     * @var array
     */
    private $point;

    /**
     * @param array $point
     */
    public function __construct(array $point)
    {
        $this->point = $point;
    }

    /**
     * @return array
     */
    public function getPoint(): array
    {
        return $this->point;
    }

    function jsonSerialize()
    {
        return ['point' => $this->point];
    }

    public function __toString()
    {
        return 'dimension space point:' . json_encode($this->point);
    }

}