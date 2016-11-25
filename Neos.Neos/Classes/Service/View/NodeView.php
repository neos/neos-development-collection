<?php
namespace Neos\Neos\Service\View;

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
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Security\Authorization\Privilege\NodeTreePrivilege;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use Neos\Utility\Arrays;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\NodePrivilegeSubject;

/**
 * An View specialized on single or multiple Nodes in a tree structure
 *
 * NOTE: This class only exists for backwards compatibility with not-yet refactored service end points and service
 *       controllers.
 *
 * @Flow\Scope("prototype")
 */
class NodeView extends JsonView
{
    /**
     * @var integer
     */
    const STYLE_LIST = 1;
    const STYLE_TREE = 2;

    /**
     * @var integer
     */
    protected $outputStyle;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * Assigns a node to the NodeView.
     *
     * @param NodeInterface $node The node to render
     * @param array $propertyNames Optional list of property names to include in the JSON output
     * @return void
     */
    public function assignNode(NodeInterface $node, array $propertyNames = array('name', 'path', 'identifier', 'properties', 'nodeType'))
    {
        $this->setConfiguration(
            array(
                'value' => array(
                    'data' => array(
                        '_only' => array('name', 'path', 'identifier', 'properties', 'nodeType'),
                        '_descend' => array('properties' => $propertyNames)
                    )
                )
            )
        );
        $this->assign('value', array('data' => $node, 'success' => true));
    }

    /**
     * @param array $nodes
     */
    public function assignNodes(array $nodes)
    {
        $data = array();
        foreach ($nodes as $node) {
            if ($node->getPath() !== '/') {
                $q = new FlowQuery(array($node));
                $closestDocumentNode = $q->closest('[instanceof Neos.Neos:Document]')->get(0);
                if ($closestDocumentNode !== null) {
                    $data[] = array(
                        'nodeContextPath' => $node->getContextPath(),
                        'documentNodeContextPath' => $closestDocumentNode->getContextPath(),
                    );
                } else {
                    $this->systemLogger->log('You have a node that is no longer connected to a parent. Path: ' . $node->getPath() . ' (Identifier: ' . $node->getIdentifier() . ')');
                }
            }
        }

        $this->assign('value', array('data' => $data, 'success' => true));
    }

    /**
     * Prepares this view to render a list or tree of child nodes of the given node.
     *
     * @param NodeInterface $node The node to fetch child nodes of
     * @param string $nodeTypeFilter Criteria for filtering the child nodes
     * @param integer $outputStyle Either STYLE_TREE or STYLE_list
     * @param integer $depth How many levels of childNodes (0 = unlimited)
     * @param NodeInterface $untilNode if given, expand all nodes on the rootline towards $untilNode, no matter what is defined with $depth.
     * @return void
     */
    public function assignChildNodes(NodeInterface $node, $nodeTypeFilter, $outputStyle = self::STYLE_LIST, $depth = 0, NodeInterface $untilNode = null)
    {
        $this->outputStyle = $outputStyle;
        $nodes = array();
        if ($this->privilegeManager->isGranted(NodeTreePrivilege::class, new NodePrivilegeSubject($node))) {
            $this->collectChildNodeData($nodes, $node, ($nodeTypeFilter === '' ? null : $nodeTypeFilter), $depth, $untilNode);
        }
        $this->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));

        $this->assign('value', array('data' => $nodes, 'success' => true));
    }

    /**
     * Prepares this view to render a list or tree of given node including child nodes.
     *
     * @param NodeInterface $node The node to fetch child nodes of
     * @param string $nodeTypeFilter Criteria for filtering the child nodes
     * @param integer $depth How many levels of childNodes (0 = unlimited)
     * @param NodeInterface $untilNode if given, expand all nodes on the rootline towards $untilNode, no matter what is defined with $depth.
     * @return void
     */
    public function assignNodeAndChildNodes(NodeInterface $node, $nodeTypeFilter = '', $depth = 0, NodeInterface $untilNode = null)
    {
        $this->outputStyle = self::STYLE_TREE;
        $data = array();
        if ($this->privilegeManager->isGranted(NodeTreePrivilege::class, new NodePrivilegeSubject($node))) {
            $childNodes = array();
            $this->collectChildNodeData($childNodes, $node, ($nodeTypeFilter === '' ? null : $nodeTypeFilter), $depth, $untilNode);
            $data = $this->collectTreeNodeData($node, true, $childNodes, $childNodes !== array());
        }
        $this->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));

        $this->assign('value', array('data' => $data, 'success' => true));
    }

    /**
     * Prepares this view to render a list or tree of filtered nodes.
     *
     * @param NodeInterface $node
     * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeData> $matchedNodes
     * @param integer $outputStyle Either STYLE_TREE or STYLE_list
     * @return void
     */
    public function assignFilteredChildNodes(NodeInterface $node, array $matchedNodes, $outputStyle = self::STYLE_LIST)
    {
        $this->outputStyle = $outputStyle;
        $nodes = $this->collectParentNodeData($node, $matchedNodes);
        $this->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));

        $this->assign('value', array('data' => $nodes, 'success' => true));
    }

    /**
     * Collect node data and traverse child nodes
     *
     * @param array &$nodes
     * @param NodeInterface $node
     * @param string $nodeTypeFilter
     * @param integer $depth levels of child nodes to fetch. 0 = unlimited
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $untilNode if given, expand all nodes on the rootline towards $untilNode, no matter what is defined with $depth.
     * @param integer $recursionPointer current recursion level
     * @return void
     */
    protected function collectChildNodeData(array &$nodes, NodeInterface $node, $nodeTypeFilter, $depth = 0, NodeInterface $untilNode = null, $recursionPointer = 1)
    {
        foreach ($node->getChildNodes($nodeTypeFilter) as $childNode) {
            if (!$this->privilegeManager->isGranted(NodeTreePrivilege::class, new NodePrivilegeSubject($childNode))) {
                continue;
            }
            /** @var NodeInterface $childNode */
            $expand = ($depth === 0 || $recursionPointer < $depth);

            if ($expand === false && $untilNode !== null && strpos($untilNode->getPath(), $childNode->getPath()) === 0 && $childNode !== $untilNode) {
                // in case $untilNode is set, and the current childNode is on the rootline of $untilNode (and not the node itself), expand the node.
                $expand = true;
            }

            switch ($this->outputStyle) {
                case self::STYLE_LIST:
                    $nodeType = $childNode->getNodeType()->getName();
                    $properties = $childNode->getProperties();
                    $properties['__contextNodePath'] = $childNode->getContextPath();
                    $properties['__workspaceName'] = $childNode->getWorkspace()->getName();
                    $properties['__nodeName'] = $childNode->getName();
                    $properties['__nodeType'] = $nodeType;
                    $properties['__title'] = $nodeType === 'Neos.Neos:Document' ? $childNode->getProperty('title') : $childNode->getLabel();
                    array_push($nodes, $properties);
                    if ($expand) {
                        $this->collectChildNodeData($nodes, $childNode, $nodeTypeFilter, $depth, $untilNode, ($recursionPointer + 1));
                    }
                break;
                case self::STYLE_TREE:
                    $children = array();
                    $hasChildNodes = $childNode->hasChildNodes($nodeTypeFilter) === true;
                    if ($expand && $hasChildNodes) {
                        $this->collectChildNodeData($children, $childNode, $nodeTypeFilter, $depth, $untilNode, ($recursionPointer + 1));
                    }
                    array_push($nodes, $this->collectTreeNodeData($childNode, $expand, $children, $hasChildNodes));
            }
        }
    }

    /**
     * @param NodeInterface $rootNode
     * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeData> $nodes
     * @return array
     */
    public function collectParentNodeData(NodeInterface $rootNode, array $nodes)
    {
        $nodeCollection = array();

        $addNode = function ($node, $matched) use ($rootNode, &$nodeCollection) {
            /** @var NodeInterface $node */
            $path = str_replace('/', '.children.', substr($node->getPath(), strlen($rootNode->getPath()) + 1));
            if ($path !== '') {
                $nodeCollection = Arrays::setValueByPath($nodeCollection, $path . '.node', $node);
                if ($matched === true) {
                    $nodeCollection = Arrays::setValueByPath($nodeCollection, $path . '.matched', true);
                }
            }
        };

        $findParent = function ($node) use (&$findParent, &$addNode) {
            /** @var NodeInterface $node */
            $parent = $node->getParent();
            if ($parent !== null) {
                $addNode($parent, false);
                $findParent($parent);
            }
        };

        foreach ($nodes as $node) {
            $addNode($node, true);
            $findParent($node);
        }

        $treeNodes = array();
        $self = $this;
        $collectTreeNodeData = function (&$treeNodes, $node) use (&$collectTreeNodeData, $self) {
            $children = array();
            if (isset($node['children'])) {
                foreach ($node['children'] as $childNode) {
                    $collectTreeNodeData($children, $childNode);
                }
            }
            $treeNodes[] = $self->collectTreeNodeData($node['node'], true, $children, $children !== array(), isset($node['matched']));
        };

        foreach ($nodeCollection as $firstLevelNode) {
            $collectTreeNodeData($treeNodes, $firstLevelNode);
        }

        return $treeNodes;
    }

    /**
     * @param NodeInterface $node
     * @param boolean $expand
     * @param array $children
     * @param boolean $hasChildNodes
     * @param boolean $matched
     * @return array
     */
    public function collectTreeNodeData(NodeInterface $node, $expand = true, array $children = array(), $hasChildNodes = false, $matched = false)
    {
        $isTimedPage = false;
        $now = new \DateTime();
        $now = $now->getTimestamp();
        $hiddenBeforeDateTime = $node->getHiddenBeforeDateTime();
        $hiddenAfterDateTime = $node->getHiddenAfterDateTime();

        if ($hiddenBeforeDateTime !== null && $hiddenBeforeDateTime->getTimestamp() > $now) {
            $isTimedPage = true;
        }
        if ($hiddenAfterDateTime !== null) {
            $isTimedPage = true;
        }

        $classes = array();
        if ($isTimedPage === true && $node->isHidden() === false) {
            array_push($classes, 'neos-timedVisibility');
        }
        if ($node->isHidden() === true) {
            array_push($classes, 'neos-hidden');
        }
        if ($node->isHiddenInIndex() === true) {
            array_push($classes, 'neos-hiddenInIndex');
        }
        if ($matched) {
            array_push($classes, 'neos-matched');
        }

        $uriBuilder = $this->controllerContext->getUriBuilder();
        $nodeType = $node->getNodeType();
        $nodeTypeConfiguration = $nodeType->getFullConfiguration();
        if ($node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $uriForNode = $uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(true)->uriFor('show', array('node' => $node), 'Frontend\Node', 'Neos.Neos');
        } else {
            $uriForNode = '#';
        }
        $label = $node->getLabel();
        $nodeTypeLabel = $node->getNodeType()->getLabel();
        $treeNode = array(
            'key' => $node->getContextPath(),
            'title' => $label,
            'fullTitle' => $node->getProperty('title'),
            'nodeTypeLabel' => $nodeTypeLabel,
            'tooltip' => '', // will be filled on the client side, because nodeTypeLabel contains the localization string instead of the localized value
            'href' => $uriForNode,
            'isFolder' => $hasChildNodes,
            'isLazy' => ($hasChildNodes && !$expand),
            'nodeType' => $nodeType->getName(),
            'isAutoCreated' => $node->isAutoCreated(),
            'expand' => $expand,
            'addClass' => implode(' ', $classes),
            'name' => $node->getName(),
            'iconClass' => isset($nodeTypeConfiguration['ui']) && isset($nodeTypeConfiguration['ui']['icon']) ? $nodeTypeConfiguration['ui']['icon'] : '',
            'isHidden' => $node->isHidden()
        );
        if ($hasChildNodes) {
            $treeNode['children'] = $children;
        }
        return $treeNode;
    }
}
