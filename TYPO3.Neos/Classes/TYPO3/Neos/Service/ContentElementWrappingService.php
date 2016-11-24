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
use TYPO3\Flow\ObjectManagement\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Service\Mapping\NodePropertyConverterService;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Service\AuthorizationService;

/**
 * The content element wrapping service adds the necessary markup around
 * a content element such that it can be edited using the Content Module
 * of the Neos Backend.
 *
 * @Flow\Scope("singleton")
 */
class ContentElementWrappingService
{
    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var AuthorizationService
     */
    protected $nodeAuthorizationService;

    /**
     * @Flow\Inject
     * @var HtmlAugmenter
     */
    protected $htmlAugmenter;

    /**
     * @Flow\Inject
     * @var NodePropertyConverterService
     */
    protected $nodePropertyConverterService;

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param NodeInterface $node
     * @param string $content
     * @param string $typoScriptPath
     * @return string
     */
    public function wrapContentObject(NodeInterface $node, $content, $typoScriptPath)
    {
        if ($this->needsMetadata($node, false) === false) {
            return $content;
        }

        $attributes = [];
        $attributes['data-node-__typoscript-path'] = $typoScriptPath;
        $attributes['tabindex'] = 0;
        $attributes = $this->addGenericEditingMetadata($attributes, $node);
        $attributes = $this->addNodePropertyAttributes($attributes, $node);
        $attributes = $this->addCssClasses($attributes, $node, $this->collectEditingClassNames($node));

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'div', array('typeof'));
    }

    /**
     * @param NodeInterface $node
     * @param string $content
     * @param string $typoScriptPath
     * @return string
     */
    public function wrapCurrentDocumentMetadata(NodeInterface $node, $content, $typoScriptPath)
    {
        if ($this->needsMetadata($node, true) === false) {
            return $content;
        }

        $attributes = [];
        $attributes['data-node-__typoscript-path'] = $typoScriptPath;
        $attributes = $this->addGenericEditingMetadata($attributes, $node);
        $attributes = $this->addNodePropertyAttributes($attributes, $node);
        $attributes = $this->addDocumentMetadata($attributes, $node);
        $attributes = $this->addCssClasses($attributes, $node, []);

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'div', ['typeof']);
    }

    /**
     * Adds node properties to the given $attributes collection and returns the extended array
     *
     * @param array $attributes
     * @param NodeInterface $node
     * @return array the merged attributes
     */
    protected function addNodePropertyAttributes(array $attributes, NodeInterface $node)
    {
        foreach (array_keys($node->getNodeType()->getProperties()) as $propertyName) {
            if ($propertyName[0] === '_' && $propertyName[1] === '_') {
                // skip fully-private properties
                continue;
            }
            $attributes = array_merge($attributes, $this->renderNodePropertyAttribute($node, $propertyName));
        }

        return $attributes;
    }

    /**
     * Renders data attributes needed for the given node property.
     *
     * @param NodeInterface $node
     * @param string $propertyName
     * @return array
     */
    protected function renderNodePropertyAttribute(NodeInterface $node, $propertyName)
    {
        $attributes = [];
        /** @var $contentContext ContentContext */
        $contentContext = $node->getContext();
        // skip the node name of the site node - TODO: Why do we need this?
        if ($propertyName === '_name' && $node === $contentContext->getCurrentSiteNode()) {
            return $attributes;
        }

        $dataType = $node->getNodeType()->getPropertyType($propertyName);
        $dasherizedPropertyName = $this->dasherize($propertyName);

        $propertyValue = $this->nodePropertyConverterService->getProperty($node, $propertyName);
        $propertyValue = $propertyValue === null ? '' : $propertyValue;
        $propertyValue = !is_string($propertyValue) ? json_encode($propertyValue) : $propertyValue;

        if ($dataType !== 'string') {
            $attributes['data-nodedatatype-' . $dasherizedPropertyName] = 'xsd:' . $dataType;
        }

        $attributes['data-node-' . $dasherizedPropertyName] = $propertyValue;

        return $attributes;
    }

    /**
     * Add required CSS classes to the attributes.
     *
     * @param array $attributes
     * @param NodeInterface $node
     * @param array $initialClasses
     * @return array
     */
    protected function addCssClasses(array $attributes, NodeInterface $node, array $initialClasses = [])
    {
        $classNames = $initialClasses;
        // FIXME: The `dimensionsAreMatchingTargetDimensionValues` method should become part of the NodeInterface if it is used here .
        if ($node instanceof Node && !$node->dimensionsAreMatchingTargetDimensionValues()) {
            $classNames[] = 'neos-contentelement-shine-through';
        }

        if ($classNames !== []) {
            $attributes['class'] = implode(' ', $classNames);
        }

        return $attributes;
    }

    /**
     * Collects metadata for the Neos backend specifically for document nodes.
     *
     * @param array $attributes
     * @param NodeInterface $node
     * @return array
     */
    protected function addDocumentMetadata(array $attributes, NodeInterface $node)
    {
        /** @var ContentContext $contentContext */
        $contentContext = $node->getContext();
        $attributes['data-neos-site-name'] = $contentContext->getCurrentSite()->getName();
        $attributes['data-neos-site-node-context-path'] = $contentContext->getCurrentSiteNode()->getContextPath();
        // Add the workspace of the content repository context to the attributes
        $attributes['data-neos-context-workspace-name'] = $contentContext->getWorkspaceName();
        $attributes['data-neos-context-dimensions'] = json_encode($contentContext->getDimensions());

        if (!$this->nodeAuthorizationService->isGrantedToEditNode($node)) {
            $attributes['data-node-__read-only'] = 'true';
            $attributes['data-nodedatatype-__read-only'] = 'boolean';
        }

        return $attributes;
    }

    /**
     * Collects metadata attributes used to allow editing of the node in the Neos backend.
     *
     * @param array $attributes
     * @param NodeInterface $node
     * @return array
     */
    protected function addGenericEditingMetadata(array $attributes, NodeInterface $node)
    {
        $attributes['typeof'] = 'typo3:' . $node->getNodeType()->getName();
        $attributes['about'] = $node->getContextPath();
        $attributes['data-node-_identifier'] = $node->getIdentifier();
        $attributes['data-node-__workspace-name'] = $node->getWorkspace()->getName();
        $attributes['data-node-__label'] = $node->getLabel();

        if ($node->getNodeType()->isOfType('TYPO3.Neos:ContentCollection')) {
            $attributes['rel'] = 'typo3:content-collection';
        }

        // these properties are needed together with the current NodeType to evaluate Node Type Constraints
        // TODO: this can probably be greatly cleaned up once we do not use CreateJS or VIE anymore.
        if ($node->getParent()) {
            $attributes['data-node-__parent-node-type'] = $node->getParent()->getNodeType()->getName();
        }

        if ($node->isAutoCreated()) {
            $attributes['data-node-_name'] = $node->getName();
            $attributes['data-node-_is-autocreated'] = 'true';
        }

        if ($node->getParent() && $node->getParent()->isAutoCreated()) {
            $attributes['data-node-_parent-is-autocreated'] = 'true';
            // we shall only add these properties if the parent is actually auto-created; as the Node-Type-Switcher in the UI relies on that.
            $attributes['data-node-__parent-node-name'] = $node->getParent()->getName();
            $attributes['data-node-__grandparent-node-type'] = $node->getParent()->getParent()->getNodeType()->getName();
        }

        return $attributes;
    }

    /**
     * Collects CSS class names used for styling editable elements in the Neos backend.
     *
     * @param NodeInterface $node
     * @return array
     */
    protected function collectEditingClassNames(NodeInterface $node)
    {
        $classNames = [];

        if ($node->getNodeType()->isOfType('TYPO3.Neos:ContentCollection')) {
            // This is needed since the backend relies on this class (should not be necessary)
            $classNames[] = 'neos-contentcollection';
        } else {
            $classNames[] = 'neos-contentelement';
        }

        if ($node->isRemoved()) {
            $classNames[] = 'neos-contentelement-removed';
        }

        if ($node->isHidden()) {
            $classNames[] = 'neos-contentelement-hidden';
        }

        if ($this->isInlineEditable($node) === false) {
            $classNames[] = 'neos-not-inline-editable';
        }

        return $classNames;
    }

    /**
     * Determine if the Node or one of it's properties is inline editable.
            $parsedType = TypeHandling::parseType($dataType);
     *
     * @param NodeInterface $node
     * @return boolean
     */
    protected function isInlineEditable(NodeInterface $node)
    {
        $uiConfiguration = $node->getNodeType()->hasConfiguration('ui') ? $node->getNodeType()->getConfiguration('ui') : [];
        return (
            (isset($uiConfiguration['inlineEditable']) && $uiConfiguration['inlineEditable'] === true) ||
            $this->hasInlineEditableProperties($node)
        );
    }

    /**
     * Checks if the given Node has any properties configured as 'inlineEditable'
     *
     * @param NodeInterface $node
     * @return boolean
     */
    protected function hasInlineEditableProperties(NodeInterface $node)
    {
        return array_reduce(array_values($node->getNodeType()->getProperties()), function ($hasInlineEditableProperties, $propertyConfiguration) {
            return ($hasInlineEditableProperties || (isset($propertyConfiguration['ui']['inlineEditable']) && $propertyConfiguration['ui']['inlineEditable'] === true));
        }, false);
    }

    /**
     * @param NodeInterface $node
     * @param boolean $renderCurrentDocumentMetadata
     * @return boolean
     */
    protected function needsMetadata(NodeInterface $node, $renderCurrentDocumentMetadata)
    {
        /** @var $contentContext ContentContext */
        $contentContext = $node->getContext();
        return ($contentContext->isInBackend() === true && ($renderCurrentDocumentMetadata === true || $this->nodeAuthorizationService->isGrantedToEditNode($node) === true));
    }

    /**
     * Converts camelCased strings to lower cased and non-camel-cased strings
     *
     * @param string $value
     * @return string
     */
    protected function dasherize($value)
    {
        return strtolower(trim(preg_replace('/[A-Z]/', '-$0', $value), '-'));
    }
}
