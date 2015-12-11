<?php
namespace TYPO3\TYPO3CR\Migration\Transformations;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * This transformation can create single or multiple nodes.
 *
 * If 'dynamicProperty' and 'dynamicPropertySource' are set, this transformation will create a new node for each element
 * in the 'dynamicPropertySource' array, setting the property specified by 'dynamicProperty' to the related value of
 * 'dynamicPropertySource'.
 *
 * Else it will create the number of identical nodes specified by the 'amount' setting (defaults to 1).
 *
 * 'path' specifies on what path relative to current node should the new nodes be created, defaults to the node itself.
 * 'type' specifies the type of nodes to be created.
 * 'properties' array specifies the default properties for the created nodes.
 */
class CreateNodes extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var string
     */
    protected $amount = 1;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $properties;

    /**
     * @var string
     */
    protected $dynamicProperty;

    /**
     * @var array
     */
    protected $dynamicPropertySource;

    /**
     * Sets the amount of nodes to create.
     *
     * @param string $amount
     * @return void
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Sets the path on which to create the node, relative to current node.
     *
     * @param string $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Sets the type of nodes to be created.
     *
     * @param string $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Sets default values for properties of nodes to be created.
     *
     * @param array $properties
     * @return void
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * Sets a value of dynamic property, that would be filled from dynamicPropertySource.
     *
     * @param string $dynamicProperty
     * @return void
     */
    public function setDynamicProperty($dynamicProperty)
    {
        $this->dynamicProperty = $dynamicProperty;
    }

    /**
     * Sets the array of values from which dynamicProperty is filled.
     *
     * @param array $dynamicPropertySource
     * @return void
     */
    public function setDynamicPropertySource(array $dynamicPropertySource)
    {
        $this->dynamicPropertySource = $dynamicPropertySource;
    }

    /**
     * All NodeData instances can be transformed by this.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return true;
    }

    /**
     * Create nodes as configured.
     *
     * @param NodeData $nodeData
     * @return void
     */
    public function execute(NodeData $nodeData)
    {
        $context = $this->nodeFactory->createContextMatchingNodeData($nodeData);
        $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
        if (isset($this->path) && !empty($this->path)) {
            $referenceNode = $node->getNode($this->path);
        } else {
            $referenceNode = $node;
        }

        $nodeType = $this->nodeTypeManager->getNodeType($this->type);
        $nodeTemplate = new NodeTemplate();
        $nodeTemplate->setNodeType($nodeType);
        if (is_array($this->properties)) {
            foreach ($this->properties as $propertyName => $propertyValue) {
                $nodeTemplate->setProperty($propertyName, $propertyValue);
            }
        }

        if (is_string($this->dynamicProperty) && is_array($this->dynamicPropertySource)) {
            foreach ($this->dynamicPropertySource as $dynamicPropertyValue) {
                $nodeTemplate->setProperty($this->dynamicProperty, $dynamicPropertyValue);
                $referenceNode->createNodeFromTemplate($nodeTemplate);
            }
        } else {
            for ($i = 0; $i < $this->amount; $i++) {
                $referenceNode->createNodeFromTemplate($nodeTemplate);
            }
        }
    }
}
