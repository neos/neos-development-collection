<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\Doctrine;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\FalseConditionGenerator;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\ConditionGenerator as EntityConditionGenerator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\DisjunctionGenerator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\PropertyConditionGenerator;
use Neos\Flow\Security\Exception\InvalidPrivilegeException;
use Neos\Flow\Validation\Validator\UuidValidator;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactory;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

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
        if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $nodePathOrIdentifier) === 1) {
            $node = $this->getNodeByIdentifier($nodePathOrIdentifier);
            if ($node === null) {
                return new FalseConditionGenerator();
            }
            $nodePath = $node->getPath();
        } else {
            $nodePath = rtrim($nodePathOrIdentifier, '/');
        }
        $propertyConditionGenerator1 = new PropertyConditionGenerator('path');
        $propertyConditionGenerator2 = new PropertyConditionGenerator('path');

        return new DisjunctionGenerator(array($propertyConditionGenerator1->like($nodePath . '/%'), $propertyConditionGenerator2->equals($nodePath)));
    }

    /**
     * @param string|array $nodeTypes
     * @return PropertyConditionGenerator
     */
    public function nodeIsOfType($nodeTypes)
    {
        $propertyConditionGenerator = new PropertyConditionGenerator('nodeType');
        if (!is_array($nodeTypes)) {
            $nodeTypes = array($nodeTypes);
        }
        $expandedNodeTypeNames = array();
        foreach ($nodeTypes as $nodeTypeName) {
            $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeTypeName, false);
            $expandedNodeTypeNames = array_merge($expandedNodeTypeNames, array($nodeTypeName), array_keys($subNodeTypes));
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
        if (!is_array($workspaceNames)) {
            $workspaceNames = array($workspaceNames);
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
