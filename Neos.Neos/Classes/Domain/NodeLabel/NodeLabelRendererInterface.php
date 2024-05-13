<?php

namespace Neos\Neos\Domain\NodeLabel;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * @api to access the Node's label in PHP, in Fusion one can use ${q(node).label()}.
 */
interface NodeLabelRendererInterface
{
    public function renderNodeLabel(Node $node): NodeLabel;
}
