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

namespace Neos\Neos\ViewHelpers\Backend;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
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

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('node', Node::class, 'Node', true);
    }

    public function render(): mixed
    {
        $node = $this->arguments['node'];
        assert($node instanceof Node);
        $documentNodes = [];
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);

        $currentNode = $node;
        while ($currentNode instanceof Node) {
            if ($currentNode->nodeType->isOfType('Neos.Neos:Document')) {
                $documentNodes[] = $currentNode;
            }
            $currentNode = $subgraph->findParentNode($currentNode->nodeAggregateIdentifier);
        }
        $documentNodes = array_reverse($documentNodes);
        $this->templateVariableContainer->add('documentNodes', $documentNodes);
        $content = $this->renderChildren();
        $this->templateVariableContainer->remove('documentNodes');

        return $content;
    }
}
