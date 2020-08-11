<?php
namespace Neos\ContentRepository\Security\Authorization\Privilege\Node\Doctrine;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Validation\Validator\NodeIdentifierValidator;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\FalseConditionGenerator;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\ConditionGenerator as EntityConditionGenerator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\DisjunctionGenerator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\PropertyConditionGenerator;
use Neos\Flow\Security\Exception\InvalidPrivilegeException;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * A SQL condition generator, supporting special SQL constraints
 * for nodes.
 */
class ConditionGenerator extends EntityConditionGenerator
{
    /**
     * @Flow\Inject
     * @var ContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var string
     */
    protected $entityType = NodeData::class;

    /**
     * @param string $entityType
     * @return boolean
     * @throws InvalidPrivilegeException
     */
    public function isType($entityType)
    {
        throw new InvalidPrivilegeException('The isType() operator must not be used in Node privilege matchers!', 1417083500);
    }

    /**
     * @param string $nodePathOrIdentifier
     * @return PropertyConditionGenerator
     */
    public function isDescendantNodeOf($nodePathOrIdentifier)
    {
        $propertyConditionGenerator1 = new PropertyConditionGenerator('path');
        $propertyConditionGenerator2 = new PropertyConditionGenerator('path');

        if (preg_match(NodeIdentifierValidator::PATTERN_MATCH_NODE_IDENTIFIER, $nodePathOrIdentifier) === 1) {
            $node = $this->getNodeByIdentifier($nodePathOrIdentifier);
            if ($node === null) {
                return new FalseConditionGenerator();
            }
            $nodePath = $node->getPath();
        } else {
            $nodePathOrIdentifier = $propertyConditionGenerator1->getValueForOperand($nodePathOrIdentifier);
            $nodePath = rtrim($nodePathOrIdentifier, '/');
        }

        return new DisjunctionGenerator([$propertyConditionGenerator1->like($nodePath . '/%'), $propertyConditionGenerator2->equals($nodePath)]);
    }

    /**
     * @param string|array $nodeTypes
     * @return PropertyConditionGenerator
     */
    public function nodeIsOfType($nodeTypes)
    {
        $propertyConditionGenerator = new PropertyConditionGenerator('nodeType');
        $nodeTypes = $propertyConditionGenerator->getValueForOperand($nodeTypes);
        if (!is_array($nodeTypes)) {
            $nodeTypes = [$nodeTypes];
        }
        $expandedNodeTypeNames = [];
        foreach ($nodeTypes as $nodeTypeName) {
            $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeTypeName, false);
            $expandedNodeTypeNames = array_merge($expandedNodeTypeNames, [$nodeTypeName], array_keys($subNodeTypes));
        }
        return $propertyConditionGenerator->in(array_unique($expandedNodeTypeNames));
    }

    /**
     * @param string|array $workspaceNames
     * @return PropertyConditionGenerator
     */
    public function isInWorkspace($workspaceNames)
    {
        $propertyConditionGenerator = new PropertyConditionGenerator('workspace');
        $workspaceNames = $propertyConditionGenerator->getValueForOperand($workspaceNames);
        if (!is_array($workspaceNames)) {
            $workspaceNames = [$workspaceNames];
        }
        return $propertyConditionGenerator->in($workspaceNames);
    }

    /**
     * @param string $nodeIdentifier
     * @return NodeInterface
     */
    protected function getNodeByIdentifier($nodeIdentifier)
    {
        $context = $this->contextFactory->create();
        $node = null;
        $this->securityContext->withoutAuthorizationChecks(function () use ($nodeIdentifier, $context, &$node) {
            $node = $context->getNodeByIdentifier($nodeIdentifier);
        });
        $context->getFirstLevelNodeCache()->setByIdentifier($nodeIdentifier, null);
        return $node;
    }
}
