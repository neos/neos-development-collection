<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\SharedModel\NodeType;

/**
 * TODO: what is the difference between NodeTypeConstraintParser and NodeTypeConstraintsFactory
 */
final class NodeTypeConstraintsFactory
{
    /**
     * @param array<string,bool> $declaration
     */
    public static function createFromNodeTypeDeclaration(array $declaration): NodeTypeConstraints
    {
        $wildCardAllowed = false;
        $explicitlyAllowedNodeTypeNames = [];
        $explicitlyDisallowedNodeTypeNames = [];
        foreach ($declaration as $constraintName => $allowed) {
            if ($constraintName === '*') {
                $wildCardAllowed = $allowed;
            } else {
                if ($allowed) {
                    $explicitlyAllowedNodeTypeNames[] = NodeTypeName::fromString($constraintName);
                } else {
                    $explicitlyDisallowedNodeTypeNames[] = NodeTypeName::fromString($constraintName);
                }
            }
        }

        return new NodeTypeConstraints(
            $wildCardAllowed,
            NodeTypeNames::fromArray($explicitlyAllowedNodeTypeNames),
            NodeTypeNames::fromArray($explicitlyDisallowedNodeTypeNames)
        );
    }
}
