<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Service;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * Renders the Node Type Schema in a format the User Interface understands;
 * additionally pre-calculating node constraints
 */
class NodeTypeSchemaBuilder
{
    private function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
    ) {
    }

    public static function create(NodeTypeManager $nodeTypeManager): self
    {
        return new self($nodeTypeManager);
    }

    /**
     * The preprocessed node type schema contains everything we need for the UI:
     *
     * - "nodeTypes" contains the original (merged) node type schema
     * - "inheritanceMap.subTypes" contains for every parent type the transitive list of subtypes
     * - "constraints" contains for each node type, the list of allowed child node types; normalizing
     *   allowlists and excludelists:
     *   - [node type]
     *     - nodeTypes:
     *       [child node type name]: true
     *     - childNodes:
     *       - [child node name]
     *         - nodeTypes:
     *          [child node type name]: true
     *
     * @return array<string,mixed> the node type schema ready to be used by the JavaScript code
     */
    public function generateNodeTypeSchema()
    {
        $schema = [
            'inheritanceMap' => [
                'subTypes' => []
            ],
            'nodeTypes' => [],
            'constraints' => $this->generateConstraints()
        ];

        $nodeTypes = $this->nodeTypeManager->getNodeTypes(true);
        /** @var NodeType $nodeType */
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            if ($nodeType->isAbstract() === false) {
                $configuration = $nodeType->getFullConfiguration();
                $configuration['properties'] = array_merge(
                    $configuration['properties'] ?? [],
                    $configuration['references'] ?? [],
                );
                unset($configuration['references']);
                $schema['nodeTypes'][$nodeTypeName] = $configuration;
                $schema['nodeTypes'][$nodeTypeName]['label'] = $nodeType->getLabel();
            }

            $schema['inheritanceMap']['subTypes'][$nodeTypeName] = [];
            foreach ($this->nodeTypeManager->getSubNodeTypes($nodeType->name, true) as $subNodeType) {
                /** @var NodeType $subNodeType */
                $schema['inheritanceMap']['subTypes'][$nodeTypeName][] = $subNodeType->name->value;
            }
        }

        return $schema;
    }

    /**
     * Generate the list of allowed sub-node-types per parent-node-type and child-node-name.
     *
     * @return array<string,mixed> constraints
     */
    protected function generateConstraints()
    {
        $constraints = [];
        $nodeTypes = $this->nodeTypeManager->getNodeTypes(true);
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            if ($nodeType->isAbstract()) {
                continue;
            }
            $constraints[$nodeTypeName] = [
                'nodeTypes' => [],
                'childNodes' => []
            ];
            foreach ($nodeTypes as $innerNodeTypeName => $innerNodeType) {
                if ($nodeType->allowsChildNodeType($innerNodeType)) {
                    $constraints[$nodeTypeName]['nodeTypes'][$innerNodeTypeName] = true;
                }
            }

            foreach ($nodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
                foreach ($nodeTypes as $innerNodeTypeName => $_x) {
                    if ($this->nodeTypeManager->isNodeTypeAllowedAsChildToTetheredNode($nodeType->name, $tetheredNodeTypeDefinition->name, NodeTypeName::fromString($innerNodeTypeName))) {
                        $constraints[$nodeTypeName]['childNodes'][$tetheredNodeTypeDefinition->name->value]['nodeTypes'][$innerNodeTypeName] = true;
                    }
                }
            }
        }

        return $constraints;
    }
}
