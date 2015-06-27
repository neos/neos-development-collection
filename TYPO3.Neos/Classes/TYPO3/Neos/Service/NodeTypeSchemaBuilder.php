<?php
namespace TYPO3\Neos\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use League\CommonMark\CommonMarkConverter;

/**
 * Renders the Node Type Schema in a format the User Interface understands; additionally pre-calculating node constraints
 *
 * @Flow\Scope("singleton")
 */
class NodeTypeSchemaBuilder
{
    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var CommonMarkConverter
     * @Flow\Inject
     */
    protected $markdownConverter;

    /**
     * The preprocessed node type schema contains everything we need for the UI:
     *
     * - "nodeTypes" contains the original (merged) node type schema
     * - "inheritanceMap.subTypes" contains for every parent type the transitive list of subtypes
     * - "constraints" contains for each node type, the list of allowed child node types; normalizing
     *   whitelists and blacklists:
     *   - [node type]
     *     - nodeTypes:
     *       [child node type name]: TRUE
     *     - childNodes:
     *       - [child node name]
     *         - nodeTypes:
     *          [child node type name]: TRUE
     *
     * @return array the node type schema ready to be used by the JavaScript code
     */
    public function generateNodeTypeSchema()
    {
        $schema = array(
            'inheritanceMap' => array(
                'subTypes' => array()
            ),
            'nodeTypes' => array(),
            'constraints' => $this->generateConstraints()
        );

        $nodeTypes = $this->nodeTypeManager->getNodeTypes(true);
        /** @var NodeType $nodeType */
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            if ($nodeType->isAbstract() === false) {
                $configuration = $nodeType->getFullConfiguration();
                $this->parseMarkdown($configuration);
                $this->flattenAlohaFormatOptions($configuration);
                $schema['nodeTypes'][$nodeTypeName] = $configuration;
                $schema['nodeTypes'][$nodeTypeName]['label'] = $nodeType->getLabel();
            }

            $schema['inheritanceMap']['subTypes'][$nodeTypeName] = array();
            foreach ($this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), true) as $subNodeType) {
                /** @var NodeType $subNodeType */
                $schema['inheritanceMap']['subTypes'][$nodeTypeName][] = $subNodeType->getName();
            }
        }

        return $schema;
    }

    /**
     * Process markdown syntax for help message properties
     *
     * @param array $options The options array, passed by reference
     * @return void
     */
    protected function parseMarkdown(array &$options)
    {
        if (isset($options['ui']['help']['message'])) {
            $options['ui']['help']['message'] = $this->markdownConverter->convertToHtml($options['ui']['help']['message']);
        }
        if (isset($options['properties'])) {
            foreach (array_keys($options['properties']) as $propertyName) {
                if (isset($options['properties'][$propertyName]['ui']['help']['message'])) {
                    $options['properties'][$propertyName]['ui']['help']['message'] = $this->markdownConverter->convertToHtml($options['properties'][$propertyName]['ui']['help']['message']);
                }
            }
        }
    }

    /**
     * In order to allow unsetting options via the YAML settings merging, the
     * formatting options can be set via 'option': TRUE, however, the frontend
     * schema expects a flattened plain numeric array. This methods adjust the setting
     * accordingly.
     *
     * @param array $options The options array, passed by reference
     * @return void
     */
    protected function flattenAlohaFormatOptions(array &$options)
    {
        if (isset($options['properties'])) {
            foreach (array_keys($options['properties']) as $propertyName) {
                if (isset($options['properties'][$propertyName]['ui']['aloha'])) {
                    foreach ($options['properties'][$propertyName]['ui']['aloha'] as $formatGroup => $settings) {
                        if (!is_array($settings) || in_array($formatGroup, array('formatlesspaste'))) {
                            continue;
                        }
                        $flattenedSettings = array();
                        foreach ($settings as $key => $option) {
                            if (is_numeric($key) && is_string($option)) {
                                $flattenedSettings[] = $option;
                            } elseif ($option === true) {
                                $flattenedSettings[] = $key;
                            }
                        }
                        $options['properties'][$propertyName]['ui']['aloha'][$formatGroup] = $flattenedSettings;
                    }
                }
            }
        }
    }

    /**
     * Generate the list of allowed sub-node-types per parent-node-type and child-node-name.
     *
     * @return array constraints
     */
    protected function generateConstraints()
    {
        $constraints = array();
        $nodeTypes = $this->nodeTypeManager->getNodeTypes(true);
        /** @var NodeType $nodeType */
        foreach ($nodeTypes as $nodeTypeName => $nodeType) {
            $constraints[$nodeTypeName] = array(
                'nodeTypes' => array(),
                'childNodes' => array()
            );
            foreach ($nodeTypes as $innerNodeTypeName => $innerNodeType) {
                if ($nodeType->allowsChildNodeType($innerNodeType)) {
                    $constraints[$nodeTypeName]['nodeTypes'][$innerNodeTypeName] = true;
                }
            }

            foreach ($nodeType->getAutoCreatedChildNodes() as $key => $_x) {
                foreach ($nodeTypes as $innerNodeTypeName => $innerNodeType) {
                    if ($nodeType->allowsGrandchildNodeType($key, $innerNodeType)) {
                        $constraints[$nodeTypeName]['childNodes'][$key]['nodeTypes'][$innerNodeTypeName] = true;
                    }
                }
            }
        }

        return $constraints;
    }
}
