<?php
namespace Neos\ContentRepository\Security\Authorization\Privilege\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * An Eel context matching expression for the CreateNodePrivilege
 */
class CreateNodePrivilegeContext extends NodePrivilegeContext
{
    /**
     * @var string|array<int,string>
     */
    protected string|array $creationNodeTypes;

    /**
     * @param string|array<int,string> $creationNodeTypes either an array of supported node type identifiers
     * or a single node type identifier (for example "Neos.Neos:Document")
     * @return boolean Has to return true, to evaluate the eel expression correctly in any case
     */
    public function createdNodeIsOfType(string|array $creationNodeTypes): bool
    {
        $this->creationNodeTypes = $creationNodeTypes;

        return true;
    }

    /**
     * @return array<int,string> $creationNodeTypes
     */
    public function getCreationNodeTypes(): array
    {
        if (is_array($this->creationNodeTypes)) {
            return $this->creationNodeTypes;
        }
        return [$this->creationNodeTypes];
    }
}
