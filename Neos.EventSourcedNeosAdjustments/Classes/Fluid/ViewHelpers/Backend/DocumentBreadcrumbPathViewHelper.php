<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Backend;


/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Render a bread crumb path by using the labels of documents leading to the given node path
 */
class DocumentBreadcrumbPathViewHelper extends AbstractViewHelper
{

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    public function render(TraversableNodeInterface $node)
    {
        $documentNodes = [];
        $flowQuery = new FlowQuery([$node]);
        $nodes = array_reverse($flowQuery->parents('[instanceof Neos.Neos:Document]')->get());
        /** @var TraversableNodeInterface $node */
        foreach ($nodes as $documentNode) {
            $documentNodes[] = $documentNode;
        }
        if ($node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $documentNodes[] = $node;
        }
        $this->templateVariableContainer->add('documentNodes', $documentNodes);
        $content = $this->renderChildren();
        $this->templateVariableContainer->remove('documentNodes');
        return $content;
    }
}
