<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\NodeLabel;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\EelEvaluatorInterface;
use Neos\Eel\Utility;
use Neos\Flow\Annotations as Flow;

/**
 * The expression based node label generator that is used as default if a label expression is configured.
 *
 * @internal please reference the interface {@see NodeLabelGeneratorInterface} instead.
 */
class ExpressionBasedNodeLabelGenerator implements NodeLabelGeneratorInterface
{
    #[Flow\Inject]
    protected EelEvaluatorInterface $eelEvaluator;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @var array<string, string>
     */
    #[Flow\InjectConfiguration('labelGenerator.eel.defaultContext', 'Neos.Neos')]
    protected ?array $defaultContextConfiguration;

    protected string $expression = <<<'EEL'
    ${(Neos.Node.nodeType(node).label || node.nodeTypeName) + (node.nodeName ? ' (' + node.nodeName + ')' : '')}
    EEL;

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function setExpression(string $expression): void
    {
        $this->expression = $expression;
    }

    /**
     * Render a node label based on an eel expression or return the expression if it is not valid eel.
     * @throws \Neos\Eel\Exception
     */
    public function getLabel(Node $node): string
    {
        $contentRepository = $this->contentRepositoryRegistry->get($node->contentRepositoryId);
        $nodeTypeManager = $contentRepository->getNodeTypeManager();
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        $expression = $this->getExpression();

        // If the node is tethered, we try to find a label expression in the parent node type configuration
        if ($node->name && $node->classification->isTethered()) {
            $parentNode = $subgraph->findParentNode($node->aggregateId);
            $parentNodeType = $parentNode ? $nodeTypeManager->getNodeType($parentNode->nodeTypeName) : null;

            if ($parentNodeType && $parentNodeType->tetheredNodeTypeDefinitions->contain($node->name)) {
                $property = 'childNodes.' . $node->name . '.label';
                if ($parentNodeType->hasConfiguration($property)) {
                    $expression = $parentNodeType->getConfiguration($property);
                }
            }
        }

        if (Utility::parseEelExpression($expression) === null) {
            return $expression;
        }

        return (string)Utility::evaluateEelExpression(
            $expression,
            $this->eelEvaluator,
            ['node' => $node],
            $this->defaultContextConfiguration ?? []
        );
    }
}
