<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

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

/**
 * An Eel context matching expression for the CreateNodePrivilege
 */
class CreateNodePrivilegeContext extends NodePrivilegeContext
{
    /**
     * @var string
     */
    protected $creationNodeTypes;

    /**
     * @param string|array $creationNodeTypes either an array of supported node type identifiers or a single node type identifier (for example "TYPO3.Neos:Document")
     * @return boolean Has to return TRUE, to evaluate the eel expression correctly in any case
     */
    public function createdNodeIsOfType($creationNodeTypes)
    {
        $this->creationNodeTypes = $creationNodeTypes;
        return true;
    }

    /**
     * @return array $creationNodeTypes
     */
    public function getCreationNodeTypes()
    {
        if (is_array($this->creationNodeTypes)) {
            return $this->creationNodeTypes;
        } elseif (is_string($this->creationNodeTypes)) {
            return array($this->creationNodeTypes);
        }
        return array();
    }
}
