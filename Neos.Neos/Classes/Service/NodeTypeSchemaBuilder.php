<?php
namespace Neos\Neos\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Renders the Node Type Schema in a format the User Interface understands; additionally pre-calculating node constraints
 *
 * @Flow\Scope("singleton")
 */
class NodeTypeSchemaBuilder
{
    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * The Neos UI package needs a few additional abstract nodetypes to be present in the schema.
     * This will be properly cleaned up when this service is moved into the Neos.UI package as we only
     * need the schema there.
     *
     * @Flow\InjectConfiguration(path="nodeTypeRoles", package="Neos.Neos.Ui")
     * @var array
     */
    protected $nodeTypeRoles;

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
     * @return array the node type schema ready to be used by the JavaScript code
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

        // We need skip abstract nodetypes as they are not instantiated by the UI
        $nodeTypes = $this->nodeTypeManager->getNodeTypes(false);

        // Get special abstract nodetypes required by the Neos.UI
        if ($this->nodeTypeRoles) {
            $additionalNodeTypeNames = array_values($this->nodeTypeRoles);
            foreach ($additionalNodeTypeNames as $additionalNodeTypeName) {
                $nodeTypes[$additionalNodeTypeName] = $this->nodeTypeManager->getNodeType($additionalNodeTypeName);
            }
        }

        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            // Skip abstract nodetypes which might have been added by the Neos.UI `nodeTypeRoles` configuration
            if ($nodeType->isAbstract() === false) {
                $configuration = $nodeType->getFullConfiguration();
                $schema['nodeTypes'][$nodeTypeName] = $configuration;
                $schema['nodeTypes'][$nodeTypeName]['label'] = $nodeType->getLabel();
            }

            // Remove the postprocessors, as they are not needed in the UI
            unset($schema['nodeTypes'][$nodeTypeName]['postprocessors']);

            $subTypes = [];
            foreach ($this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), false) as $subNodeType) {
                $subTypes[] = $subNodeType->getName();
            }
            if ($subTypes) {
                $schema['inheritanceMap']['subTypes'][$nodeTypeName] = $subTypes;
            }
        }

        return $schema;
    }

    /**
     * Generate the list of allowed sub-node-types per parent-node-type and child-node-name.
     *
     * @return array constraints
     */
    protected function generateConstraints()
    {
        $constraints = [];
        $nodeTypes = $this->nodeTypeManager->getNodeTypes(false);
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            $nodeTypeConstraints = [];
            $childNodeConstraints = [];

            foreach ($nodeTypes as $innerNodeTypeName => $innerNodeType) {
                if ($nodeType->allowsChildNodeType($innerNodeType)) {
                    $nodeTypeConstraints[$innerNodeTypeName] = true;
                }
            }

            foreach (array_keys($nodeType->getAutoCreatedChildNodes()) as $key) {
                foreach ($nodeTypes as $innerNodeTypeName => $innerNodeType) {
                    if ($nodeType->allowsGrandchildNodeType($key, $innerNodeType)) {
                        $childNodeConstraints[$key]['nodeTypes'][$innerNodeTypeName] = true;
                    }
                }
            }

            if ($nodeTypeConstraints || $childNodeConstraints) {
                $constraints[$nodeTypeName] = [
                    'nodeTypes' => $nodeTypeConstraints,
                    'childNodes' => $childNodeConstraints,
                ];
            }
        }

        return $constraints;
    }
}
