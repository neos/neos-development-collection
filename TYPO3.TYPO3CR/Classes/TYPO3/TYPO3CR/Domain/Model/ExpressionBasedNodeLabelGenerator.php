<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\EelEvaluatorInterface;
use TYPO3\Eel\Utility;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use TYPO3\Flow\Utility\Unicode\Functions;

/**
 * The expression based node label generator that is used as default if a label expression is configured.
 *
 */
class ExpressionBasedNodeLabelGenerator implements NodeLabelGeneratorInterface
{
    /**
     * @Flow\Inject
     * @var EelEvaluatorInterface
     */
    protected $eelEvaluator;

    /**
     * @Flow\InjectConfiguration("labelGenerator.eel.defaultContext")
     * @var array
     */
    protected $defaultContextConfiguration;

    /**
     * @var string
     */
    protected $expression = '${(node.nodeType.label ? node.nodeType.label : node.nodeType.name) + \' (\' + node.name + \')\'}';

    /**
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @param string $expression
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;
    }

    /**
     * @return void
     */
    public function initializeObject()
    {
        if ($this->eelEvaluator instanceof DependencyProxy) {
            $this->eelEvaluator->_activateDependency();
        }
    }

    /**
     * Render a node label
     *
     * @param NodeInterface $node
     * @param boolean $crop This argument is deprecated as of Neos 1.2 and will be removed. Don't rely on this behavior and crop labels in the view.
     * @return string
     */
    public function getLabel(NodeInterface $node, $crop = true)
    {
        $label = Utility::evaluateEelExpression($this->getExpression(), $this->eelEvaluator, array('node' => $node), $this->defaultContextConfiguration);

        if ($crop === false) {
            return $label;
        }

        $croppedLabel = Functions::substr($label, 0, 30);
        return $croppedLabel . (strlen($croppedLabel) < strlen($label) ? ' …' : '');
    }
}
