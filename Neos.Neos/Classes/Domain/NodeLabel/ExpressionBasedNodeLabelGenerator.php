<?php

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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
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
    /**
     * @Flow\Inject
     */
    protected EelEvaluatorInterface $eelEvaluator;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos", path="labelGenerator.eel.defaultContext")
     * @var array<string, string>
     */
    protected $defaultContextConfiguration;

    /**
     * @var string
     */
    protected $expression = <<<'EEL'
    ${(node.nodeType.label ? node.nodeType.label : node.nodeTypeName.value) + (node.nodeName ? ' (' + node.nodeName.value + ')' : '')}
    EEL;

    /**
     * @return string
     */
    public function getExpression()
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
        if (Utility::parseEelExpression($this->getExpression()) === null) {
            return $this->getExpression();
        }
        $value = Utility::evaluateEelExpression($this->getExpression(), $this->eelEvaluator, ['node' => $node], $this->defaultContextConfiguration);
        return (string)$value;
    }
}
