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
use Neos\Flow\Annotations as Flow;

/**
 * The node type constraints value object
 */
final class NodeTypeConstraints implements \JsonSerializable
{
    /**
     * @var array
     */
    protected $constraints;


    public function __construct(array $constraints)
    {
        $this->constraints = $constraints;
    }


    public function getConstraints(): array
    {
        return $this->constraints;
    }

    function jsonSerialize(): array
    {
        return $this->constraints;
    }
}
