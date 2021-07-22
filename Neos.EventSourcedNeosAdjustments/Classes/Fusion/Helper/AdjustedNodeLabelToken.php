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
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;
use Neos\Neos\Fusion\Helper\NodeLabelToken;

class AdjustedNodeLabelToken extends NodeLabelToken
{

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    public function __construct(NodeInterface $node = null)
    {
        $this->node = $node;
    }

    /**
     * Sets the label and postfix based on the nodetype
     */
    protected function resolveLabelFromNodeType(): void
    {
        $this->label = $this->translationHelper->translate($this->node->getNodeType()->getLabel());
        if (empty($this->label)) {
            $this->label = $this->node->getNodeType()->getName();
        }

        $nodeAccessor = $this->nodeAccessorManager->accessorFor($this->node->getContentStreamIdentifier(), $this->node->getDimensionSpacePoint(), $this->node->getVisibilityConstraints());
        if (empty($this->postfix) && NodeInfoHelper::isAutoCreated($this->node, $nodeAccessor)) {
            $this->postfix =  ' (' . $this->node->getNodeName()->jsonSerialize() . ')';
        }
    }
}
