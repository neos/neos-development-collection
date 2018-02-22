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
final class NodeTypeConstraints
{
    /**
     * @var bool
     */
    protected $wildcardAllowed;

    /**
     * @var array|NodeTypeName[]
     */
    protected $explicitlyAllowedNodeTypeNames;

    /**
     * @var array|NodeTypeName[]
     */
    protected $explicitlyDisallowedNodeTypeNames;


    /**
     * @param bool $wildCardAllowed
     * @param array $explicitlyAllowedNodeTypeNames
     * @param array $explicitlyDisallowedNodeTypeNames
     */
    public function __construct(bool $wildCardAllowed, array $explicitlyAllowedNodeTypeNames = [], array $explicitlyDisallowedNodeTypeNames = [])
    {
        $this->wildcardAllowed = $wildCardAllowed;
        $this->explicitlyAllowedNodeTypeNames = $explicitlyAllowedNodeTypeNames;
        $this->explicitlyDisallowedNodeTypeNames = $explicitlyDisallowedNodeTypeNames;
    }


    /**
     * @return bool
     */
    public function isWildcardAllowed(): bool
    {
        return $this->wildcardAllowed;
    }

    /**
     * @return array|NodeTypeName[]
     */
    public function getExplicitlyAllowedNodeTypeNames(): array
    {
        return $this->explicitlyAllowedNodeTypeNames;
    }

    /**
     * @return array|NodeTypeName[]
     */
    public function getExplicitlyDisallowedNodeTypeNames(): array
    {
        return $this->explicitlyDisallowedNodeTypeNames;
    }
}
