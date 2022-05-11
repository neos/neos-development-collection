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

use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\Content\NodeInterface;
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
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('node', NodeInterface::class, 'Node', true);
    }

    public function render(): mixed
    {
        $node = $this->arguments['node'];
        assert($node instanceof NodeInterface);
        $documentNodes = [];
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            $node->getVisibilityConstraints()
        );
        $currentNode = $node;
        while ($currentNode instanceof NodeInterface) {
            if ($currentNode->getNodeType()->isOfType('Neos.Neos:Document')) {
                $documentNodes[] = $currentNode;
            }
            $currentNode = $nodeAccessor->findParentNode($currentNode);
        }
        $documentNodes = array_reverse($documentNodes);
        $this->templateVariableContainer->add('documentNodes', $documentNodes);
        $content = $this->renderChildren();
        $this->templateVariableContainer->remove('documentNodes');

        return $content;
    }
}
