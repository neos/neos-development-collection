<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Neos\Fusion\Helper\NodeLabelToken;

class AdjustedNodeLabelToken extends NodeLabelToken
{
    /**
     * @var ?NodeInterface
     */
    protected $node;

    public function __construct(NodeInterface $node = null)
    {
        $this->node = $node;
    }

    /**
     * Sets the label and postfix based on the nodetype
     */
    protected function resolveLabelFromNodeType(): void
    {
        if (is_null($this->node)) {
            $this->label = '';
            return;
        }
        $this->label = $this->translationHelper->translate($this->node->getNodeType()->getLabel()) ?: '';
        if (empty($this->label)) {
            $this->label = $this->node->getNodeType()->getName();
        }

        if (empty($this->postfix) && $this->node->isTethered()) {
            $this->postfix =  ' (' . $this->node->getNodeName() . ')';
        }
    }
}
