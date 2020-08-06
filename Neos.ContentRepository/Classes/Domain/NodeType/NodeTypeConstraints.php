<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\NodeType;

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
 * The list of node type constraints needed for various find() operations on the node tree.
 *
 * Never create an instance of this object by hand; rather use {@see \Neos\ContentRepository\Domain\Factory\NodeTypeConstraintFactory}
 *
 * @Flow\Proxy(false)
 * @api
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
     * @param array|NodeTypeName[] $explicitlyAllowedNodeTypeNames
     * @param array|NodeTypeName[] $explicitlyDisallowedNodeTypeNames
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

    public function matches(NodeTypeName $nodeTypeName)
    {
        // if $nodeTypeName is explicitely excluded, it is DENIED.
        foreach ($this->explicitlyDisallowedNodeTypeNames as $disallowed) {
            if ((string)$nodeTypeName === (string)$disallowed) {
                return false;
            }
        }

        // if $nodeTypeName is explicitely ALLOWED.
        foreach ($this->explicitlyAllowedNodeTypeNames as $allowed) {
            if ((string)$nodeTypeName === (string)$allowed) {
                return true;
            }
        }

        // otherwise, we return $wildcardAllowed.
        return $this->wildcardAllowed;
    }

    /**
     * IMMUTABLE, returns a new instance
     *
     * @param NodeTypeName $nodeTypeName
     * @return NodeTypeConstraints
     */
    public function withExplicitlyDisallowedNodeType(NodeTypeName $nodeTypeName): NodeTypeConstraints
    {
        $disallowedNodeTypeNames = $this->explicitlyDisallowedNodeTypeNames;
        $disallowedNodeTypeNames[] = $nodeTypeName;
        return new NodeTypeConstraints($this->wildcardAllowed, $this->explicitlyAllowedNodeTypeNames, $disallowedNodeTypeNames);
    }

    /**
     * return the legacy (pre-event-sourced) Node Type filter string looking like "Foo:Bar,!MyPackage:Exclude"
     * @deprecated
     */
    public function asLegacyNodeTypeFilterString(): string
    {
        $legacyParts = [];
        foreach ($this->explicitlyDisallowedNodeTypeNames as $nodeTypeName) {
            $legacyParts[] = '!' . (string)$nodeTypeName;
        }

        foreach ($this->explicitlyAllowedNodeTypeNames as $nodeTypeName) {
            $legacyParts[] = (string)$nodeTypeName;
        }

        return implode(',', $legacyParts);
    }
}
