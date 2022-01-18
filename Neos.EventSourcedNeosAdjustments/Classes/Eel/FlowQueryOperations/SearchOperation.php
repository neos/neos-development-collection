<?php
namespace Neos\EventSourcedNeosAdjustments\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\SearchTerm;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * Custom search operation using the Content Graph fulltext search
 *
 * Original implementation: \Neos\Neos\Ui\FlowQueryOperations\SearchOperation
 */
class SearchOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'search';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 110;

    /**
     * {@inheritdoc}
     *
     * @var boolean
     */
    protected static $final = true;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * {@inheritdoc}
     *
     * We can only handle ContentRepository Nodes.
     *
     * @param mixed $context
     * @return boolean
     */
    public function canEvaluate($context)
    {
        return (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        /** @var NodeInterface $contextNode */
        $contextNode = $flowQuery->getContext()[0];
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $contextNode->getContentStreamIdentifier(),
            $contextNode->getDimensionSpacePoint(),
            $contextNode->getVisibilityConstraints()
        );
        $nodes = $nodeAccessor->findDescendants(
            [$contextNode],
            $this->nodeTypeConstraintFactory->parseFilterString($arguments[1] ?? ''),
            SearchTerm::fulltext($arguments[0] ?? '')
        );
        $flowQuery->setContext($nodes);
    }
}
