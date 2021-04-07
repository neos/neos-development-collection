<?php
namespace Neos\Neos\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\Helper\StringHelper;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\EelHelper\TranslationHelper;

/**
 * Provides a chainable interface to build a label for a nodetype
 * with a fallback mechanism.
 */
class NodeLabelToken implements ProtectedContextAwareInterface
{

    /**
     * @Flow\Inject
     * @var TranslationHelper
     */
    protected $translationHelper;

    /**
     * @Flow\Inject
     * @var StringHelper
     */
    protected $stringHelper;

    /**
     * @var string
     */
    protected $label = '';

    /**
     * @var int
     */
    protected $limit = 100;

    /**
     * @var string
     */
    protected $suffix = '...';

    /**
     * @var NodeInterface
     */
    protected $node = null;

    public function __construct(NodeInterface $node = null, string $value = null)
    {
        $this->node = $node;
        $this->label = $value;
    }

    public function or(string $fallback = null): NodeLabelToken
    {
        if (!$this->label && $fallback) {
            $this->label = $fallback;
        }
        return $this;
    }

    public function limit(int $limit): NodeLabelToken
    {
        $this->limit = $limit;
        return $this;
    }

    public function suffix(string $suffix): NodeLabelToken
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * Runs evaluate to avoid the need of calling evaluate as a finishing method
     */
    public function __toString(): string
    {
        return $this->evaluate();
    }

    /**
     * Crops, removes special chars & tags and trim the label.
     * If the label is empty a fallback based on the nodetype is provided.
     */
    public function evaluate(): string
    {
        $label = $this->label;

        if (!$label && $this->node) {
            $label = $this->getNodeTypeFallbackLabel();
        }
        $label = preg_replace('/<br\\W*?\\/?>|\\x{00a0}|[^[:print:]]|\\s+/u', ' ', $label);
        $label = strip_tags($label);
        $label = trim($label);
        $label = $this->stringHelper->cropAtWord($label, $this->limit, $this->suffix);

        return $label;
    }

    /**
     * Returns a label based on the nodetype
     */
    protected function getNodeTypeFallbackLabel(): string
    {
        if (!$this->node) {
            return '';
        }
        $nodeTypeLabel = $this->translationHelper->translate($this->node->getNodeType()->getLabel());
        if (!$nodeTypeLabel) {
            $nodeTypeLabel = $this->node->getNodeType()->getName();
        }
        return $nodeTypeLabel . ($this->node->isAutoCreated() ? ' (' . $this->node->getName() . ')' : '');
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
