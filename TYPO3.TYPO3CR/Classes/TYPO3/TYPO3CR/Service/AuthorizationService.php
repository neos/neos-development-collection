<?php
namespace TYPO3\TYPO3CR\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilege;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilegeSubject;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePropertyPrivilege;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\NodePrivilegeSubject;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\PropertyAwareNodePrivilegeSubject;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\ReadNodePropertyPrivilege;
use TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\RemoveNodePrivilege;

/**
 * This service provides API methods to check for privileges
 * on nodes and permissions for node actions.
 *
 * @Flow\Scope("singleton")
 */
class AuthorizationService
{
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * Returns TRUE if the currently authenticated user is allowed to edit the given $node, otherwise FALSE
     *
     * @param NodeInterface $node
     * @return boolean
     */
    public function isGrantedToEditNode(NodeInterface $node)
    {
        return $this->privilegeManager->isGranted(EditNodePrivilege::class, new NodePrivilegeSubject($node));
    }

    /**
     * Returns TRUE if the currently authenticated user is allowed to create a node of type $typeOfNewNode within the given $referenceNode
     *
     * @param NodeInterface $referenceNode
     * @param NodeType $typeOfNewNode
     * @return boolean
     */
    public function isGrantedToCreateNode(NodeInterface $referenceNode, NodeType $typeOfNewNode = null)
    {
        return $this->privilegeManager->isGranted(CreateNodePrivilege::class, new CreateNodePrivilegeSubject($referenceNode, $typeOfNewNode));
    }

    /**
     * Returns the node types that the currently authenticated user is *denied* to create within the given $referenceNode
     *
     * @param NodeInterface $referenceNode
     * @return string[] Array of granted node type names
     */
    public function getNodeTypeNamesDeniedForCreation(NodeInterface $referenceNode)
    {
        $privilegeSubject = new CreateNodePrivilegeSubject($referenceNode);

        $allNodeTypes = $this->nodeTypeManager->getNodeTypes();

        $deniedCreationNodeTypes = array();
        $grantedCreationNodeTypes = array();
        $abstainedCreationNodeTypes = array();
        foreach ($this->securityContext->getRoles() as $role) {
            /** @var CreateNodePrivilege $createNodePrivilege */
            foreach ($role->getPrivilegesByType(CreateNodePrivilege::class) as $createNodePrivilege) {
                if (!$createNodePrivilege->matchesSubject($privilegeSubject)) {
                    continue;
                }

                $affectedNodeTypes = ($createNodePrivilege->getCreationNodeTypes() !== array() ? $createNodePrivilege->getCreationNodeTypes() : $allNodeTypes);

                if ($createNodePrivilege->isGranted()) {
                    $grantedCreationNodeTypes = array_merge($grantedCreationNodeTypes, $affectedNodeTypes);
                } elseif ($createNodePrivilege->isDenied()) {
                    $deniedCreationNodeTypes = array_merge($deniedCreationNodeTypes, $affectedNodeTypes);
                } else {
                    $abstainedCreationNodeTypes = array_merge($abstainedCreationNodeTypes, $affectedNodeTypes);
                }
            }
        }
        $implicitlyDeniedNodeTypes = array_diff($abstainedCreationNodeTypes, $grantedCreationNodeTypes);
        return array_merge($implicitlyDeniedNodeTypes, $deniedCreationNodeTypes);
    }

    /**
     * Returns TRUE if the currently authenticated user is allowed to remove the given $node
     *
     * @param NodeInterface $node
     * @return boolean
     */
    public function isGrantedToRemoveNode(NodeInterface $node)
    {
        $privilegeSubject = new NodePrivilegeSubject($node);
        return $this->privilegeManager->isGranted(RemoveNodePrivilege::class, $privilegeSubject);
    }

    /**
     * @param NodeInterface $node
     * @param string $propertyName
     * @return boolean
     */
    public function isGrantedToReadNodeProperty(NodeInterface $node, $propertyName)
    {
        $privilegeSubject = new PropertyAwareNodePrivilegeSubject($node, null, $propertyName);
        return $this->privilegeManager->isGranted(ReadNodePropertyPrivilege::class, $privilegeSubject);
    }

    /**
     * @param NodeInterface $node
     * @param string $propertyName
     * @return boolean
     */
    public function isGrantedToEditNodeProperty(NodeInterface $node, $propertyName)
    {
        $privilegeSubject = new PropertyAwareNodePrivilegeSubject($node, null, $propertyName);
        return $this->privilegeManager->isGranted(EditNodePropertyPrivilege::class, $privilegeSubject);
    }

    /**
     * @param NodeInterface $node
     * @return string[] Array of granted node property names
     */
    public function getDeniedNodePropertiesForEditing(NodeInterface $node)
    {
        $privilegeSubject = new PropertyAwareNodePrivilegeSubject($node);

        $deniedNodePropertyNames = array();
        $grantedNodePropertyNames = array();
        $abstainedNodePropertyNames = array();
        foreach ($this->securityContext->getRoles() as $role) {
            /** @var EditNodePropertyPrivilege $editNodePropertyPrivilege */
            foreach ($role->getPrivilegesByType(EditNodePropertyPrivilege::class) as $editNodePropertyPrivilege) {
                if (!$editNodePropertyPrivilege->matchesSubject($privilegeSubject)) {
                    continue;
                }
                if ($editNodePropertyPrivilege->isGranted()) {
                    $grantedNodePropertyNames = array_merge($grantedNodePropertyNames, $editNodePropertyPrivilege->getNodePropertyNames());
                } elseif ($editNodePropertyPrivilege->isDenied()) {
                    $deniedNodePropertyNames = array_merge($deniedNodePropertyNames, $editNodePropertyPrivilege->getNodePropertyNames());
                } else {
                    $abstainedNodePropertyNames = array_merge($abstainedNodePropertyNames, $editNodePropertyPrivilege->getNodePropertyNames());
                }
            }
        }

        $implicitlyDeniedNodePropertyNames = array_diff($abstainedNodePropertyNames, $grantedNodePropertyNames);
        return array_merge($implicitlyDeniedNodePropertyNames, $deniedNodePropertyNames);
    }
}
