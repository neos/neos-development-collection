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

use Neos\ContentRepository\Domain\Projection\Workspace\Workspace;
use Neos\ContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Context\Content\NodeAddress;
use Neos\Neos\Domain\Context\Content\NodeAddressFactory;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Domain\Context\Content\NodeSiteResolvingService;
use Neos\Neos\Service\Mapping\NodePropertyConverterService;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\Fusion\Service\HtmlAugmenter as FusionHtmlAugmenter;

/**
 * The content element wrapping service adds the necessary markup around
 * a content element such that it can be edited using the Content Module
 * of the Neos Backend.
 *
 * @Flow\Scope("singleton")
 */
class ContentElementWrappingService implements ContentElementWrappingServiceInterface
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
     * @var FusionHtmlAugmenter
     */
    protected $htmlAugmenter;

    /**
     * @Flow\Inject
     * @var NodePropertyConverterService
     */
    protected $nodePropertyConverterService;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressService;

    /**
     * @Flow\Inject
     * @var NodeSiteResolvingService
     */
    protected $nodeSiteResolvingService;

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param NodeInterface $node
     * @param ContentSubgraphInterface $subgraph
     * @param string $content
     * @param string $fusionPath
     * @param array $additionalAttributes additional attributes in the form ['<attribute-name>' => '<attibute-value>', ...] to be rendered in the element wrapping
     * @return string
     */
    public function wrapContentObject(NodeInterface $node, ContentSubgraphInterface $subgraph, $content, $fusionPath, array $additionalAttributes = []): string
    {
        $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($subgraph->getContentStreamIdentifier());
        if ($this->needsMetadata($node, $workspace,false) === false) {
            return $content;
        }
        $attributes = $additionalAttributes;
        $attributes['data-node-__typoscript-path'] = $fusionPath; // @deprecated
        $attributes['data-node-__fusion-path'] = $fusionPath;
        $attributes['tabindex'] = 0;
        $attributes = $this->addGenericEditingMetadata($attributes, $node, $subgraph, $workspace);
        $attributes = $this->addNodePropertyAttributes($attributes, $node);
        $attributes = $this->addCssClasses($attributes, $node, $subgraph, $this->collectEditingClassNames($node));

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'div', array('typeof'));
    }

    /**
     * @param NodeInterface $node
     * @param ContentSubgraphInterface $subgraph
     * @param string $content
     * @param string $fusionPath
     * @param array $additionalAttributes additional attributes in the form ['<attribute-name>' => '<attibute-value>', ...] to be rendered in the element wrapping
     * @return string
     */
    public function wrapCurrentDocumentMetadata(NodeInterface $node, ContentSubgraphInterface $subgraph, $content, $fusionPath, array $additionalAttributes = []): string
    {
        $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($subgraph->getContentStreamIdentifier());
        if ($this->needsMetadata($node, $workspace, true) === false) {
            return $content;
        }
        $attributes = $additionalAttributes;
        $attributes['data-node-__typoscript-path'] = $fusionPath; // @deprecated
        $attributes['data-node-__fusion-path'] = $fusionPath;
        $attributes = $this->addGenericEditingMetadata($attributes, $node, $subgraph, $workspace);
        $attributes = $this->addNodePropertyAttributes($attributes, $node);
        $attributes = $this->addDocumentMetadata($attributes, $node, $subgraph, $workspace);
        $attributes = $this->addCssClasses($attributes, $node, $subgraph, []);

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'div', ['typeof']);
    }

    /**
     * Adds node properties to the given $attributes collection and returns the extended array
     *
     * @param array $attributes
     * @param NodeInterface $node
     * @return array the merged attributes
     */
    protected function addNodePropertyAttributes(array $attributes, NodeInterface $node): array
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
    protected function renderNodePropertyAttribute(NodeInterface $node, $propertyName): array
    {
        $attributes = [];
        // skip the node name of the site node - TODO: Why do we need this?
        if ($propertyName === '_name' && $node->getNodeType()->isOfType('Neos.Neos:Site')) {
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
     * @param ContentSubgraphInterface $subgraph
     * @param array $initialClasses
     * @return array
     */
    protected function addCssClasses(array $attributes, NodeInterface $node, ContentSubgraphInterface $subgraph, array $initialClasses = [])
    {
        $classNames = $initialClasses;

        if (!$node->getDimensionSpacePoint()->equals($subgraph->getDimensionSpacePoint())) {
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
     * @param ContentSubgraphInterface $subgraph
     * @param Workspace $workspace
     * @return array
     */
    protected function addDocumentMetadata(array $attributes, NodeInterface $node, ContentSubgraphInterface $subgraph, Workspace $workspace)
    {
        $nodeAddress = new NodeAddress($subgraph->getContentStreamIdentifier(), $subgraph->getDimensionSpacePoint(), $node->getNodeAggregateIdentifier());
        $siteNode = $this->nodeSiteResolvingService->findSiteNodeForNodeAddress($nodeAddress);
        $attributes['data-neos-site-name'] = $siteNode->getNodeName();
        $attributes['data-neos-site-node-context-path'] = $siteNode->getContextPath();
        // Add the workspace of the content repository context to the attributes
        $attributes['data-neos-context-workspace-name'] = $workspace->getWorkspaceName();
        $attributes['data-neos-context-dimensions'] = json_encode($subgraph->getDimensionSpacePoint());

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
     * @param ContentSubgraphInterface $subgraph
     * @param Workspace $workspace
     * @return array
     */
    protected function addGenericEditingMetadata(array $attributes, NodeInterface $node, ContentSubgraphInterface $subgraph, Workspace $workspace)
    {
        $attributes['typeof'] = 'typo3:' . $node->getNodeType()->getName();
        $attributes['about'] = $node->getContextPath();
        $attributes['data-node-_identifier'] = $node->getNodeAggregateIdentifier();
        $attributes['data-node-__workspace-name'] = $workspace->getWorkspaceName();
        $attributes['data-node-__label'] = $node->getLabel();

        if ($node->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
            $attributes['rel'] = 'typo3:content-collection';
        }

        // these properties are needed together with the current NodeType to evaluate Node Type Constraints
        // TODO: this can probably be greatly cleaned up once we do not use CreateJS or VIE anymore.
        $parentNode = $subgraph->findParentNode($node->getNodeIdentifier());
        if ($parentNode) {
            $attributes['data-node-__parent-node-type'] = $parentNode->getNodeTypeName();
            if ($parentNode->getNodeType()->hasAutoCreatedChildNodeWithNodeName($node->getNodeName())) {
                $attributes['data-node-_name'] = $node->getNodeName();
                $attributes['data-node-_is-autocreated'] = 'true';
            }

            $grandParentNode = $subgraph->findParentNode($parentNode->getNodeIdentifier());
            if ($grandParentNode && $grandParentNode->getNodeType()->hasAutoCreatedChildNodeWithNodeName($parentNode->getNodeName())) {
                $attributes['data-node-_parent-is-autocreated'] = 'true';
                // we shall only add these properties if the parent is actually auto-created; as the Node-Type-Switcher in the UI relies on that.
                $attributes['data-node-__parent-node-name'] = $parentNode->getNodeName();
                $attributes['data-node-__grandparent-node-type'] = $grandParentNode->getNodeTypeName();
            }
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

        if ($node->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
            // This is needed since the backend relies on this class (should not be necessary)
            $classNames[] = 'neos-contentcollection';
        } else {
            $classNames[] = 'neos-contentelement';
        }

        /*
        if ($node->isRemoved()) {
            $classNames[] = 'neos-contentelement-removed';
        }*/

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
     * $parsedType = TypeHandling::parseType($dataType);
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
     * @param Workspace $workspace
     * @param boolean $renderCurrentDocumentMetadata
     * @return boolean
     */
    protected function needsMetadata(NodeInterface $node, Workspace $workspace, $renderCurrentDocumentMetadata)
    {
        return (!$workspace->getWorkspaceName()->isLive()
            && ($renderCurrentDocumentMetadata === true || $this->nodeAuthorizationService->isGrantedToEditNode($node) === true));
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
