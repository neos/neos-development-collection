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

use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;

/**
 * An Eel context matching expression for the CreateNodePrivilege
 */
class CreateNodePrivilegeContext extends NodePrivilegeContext
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var string[]
     */
    protected $creationNodeTypes = [];

    /**
     * @param string|string[] $creationNodeTypes either an array of supported node type identifiers or a single node type identifier (for example "Neos.Neos:Document")
     * @param bool $includeSubNodeTypes indicates if the sub node types should be added to the allowed node types
     *
     * @return bool Has to return true, to evaluate the eel expression correctly in any case
     */
    public function createdNodeIsOfType($creationNodeTypes, bool $includeSubNodeTypes = false)
    {
        $this->creationNodeTypes = is_array($creationNodeTypes) ? $creationNodeTypes : [$creationNodeTypes];

        if ($includeSubNodeTypes) {
            $creationNodeTypeNames = [];
            foreach ($this->creationNodeTypes as $creationNodeTypeName) {
                $creationNodeTypeNames[$creationNodeTypeName] = true;
                $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes($creationNodeTypeName, false);
                foreach ($subNodeTypes as $subNodeTypeName => $subNodeType) {
                    $creationNodeTypeNames[$subNodeTypeName] = true;
                }
            }
            $this->creationNodeTypes = array_keys($creationNodeTypeNames);
        }

        return true;
    }

    /**
     * @return string[] $creationNodeTypes
     */
    public function getCreationNodeTypes()
    {
        return $this->creationNodeTypes;
    }
}
