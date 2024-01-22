<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion\Helper;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\Helper\StringHelper;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;

/**
 * Provides a chainable interface to build a label for a nodetype
 * with a fallback mechanism.
 */
class NodeLabelToken implements ProtectedContextAwareInterface
{
    use NodeTypeWithFallbackProvider;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

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
    protected $length = 100;

    /**
     * @var string
     */
    protected $suffix = '…';

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @var string
     */
    protected $postfix = '';

    protected Node $node;

    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    public function override(string $override = null): NodeLabelToken
    {
        if (empty($this->label) && $override) {
            $this->label = $override;
        }
        return $this;
    }

    public function crop(int $length, string $suffix = '...'): NodeLabelToken
    {
        $this->length = $length;
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * Add a text before the main label
     */
    public function prefix(string $prefix): NodeLabelToken
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Add a text after the main label (can will be cropped)
     */
    public function postfix(string $postfix): NodeLabelToken
    {
        $this->postfix = $postfix;
        return $this;
    }

    public function properties(string ...$propertyNames): NodeLabelToken
    {
        foreach ($propertyNames as $propertyName) {
            if ($this->node->hasProperty($propertyName) && !empty($this->node->getProperty($propertyName))) {
                $this->label = $this->node->getProperty($propertyName);
                break;
            }
        }
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
        if (empty($this->label)) {
            $this->resolveLabelFromNodeType();
        }

        return $this->sanitiseLabel($this->prefix . $this->label . $this->postfix);
    }

    /**
     * Sets the label and postfix based on the nodetype
     */
    protected function resolveLabelFromNodeType(): void
    {
        $this->label = $this->translationHelper->translate($this->getNodeType($this->node)->getLabel()) ?: '';
        if (empty($this->label)) {
            $this->label = $this->node->nodeTypeName->value;
        }

        if (empty($this->postfix) && $this->node->nodeName !== null && $this->node->classification->isTethered()) {
            $this->postfix =  ' (' . $this->node->nodeName->value . ')';
        }
    }

    protected function sanitiseLabel(string $label): string
    {
        $label = preg_replace('/<br\\W*?\\/?>|\\x{00a0}|[^[:print:]]|\\s+/u', ' ', $label) ?: '';
        $label = strip_tags($label);
        $label = trim($label);
        $label = $this->stringHelper->cropAtWord($label, $this->length, $this->suffix);

        return $label;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
