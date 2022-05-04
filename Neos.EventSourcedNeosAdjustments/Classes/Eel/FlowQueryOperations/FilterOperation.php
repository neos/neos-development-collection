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

use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Utility\ObjectAccess;

/**
 * This filter implementation contains specific behavior for use on ContentRepository
 * nodes. It will not evaluate any elements that are not instances of the
 * `NodeInterface`.
 *
 * The implementation changes the behavior of the `instanceof` operator to
 * work on node types instead of PHP object types, so that::
 *
 *  [instanceof Neos.NodeTypes:Page]
 *
 * will in fact use `isOfType()` on the `NodeType` of context elements to
 * filter. This filter allow also to filter the current context by a given
 * node. Anything else remains unchanged.
 */
class FilterOperation extends \Neos\ContentRepository\Eel\FlowQueryOperations\FilterOperation
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 500;

    /**
     * {@inheritdoc}
     *
     * @param NodeInterface $element
     * @param string $propertyPath
     * @return mixed
     */
    protected function getPropertyPath($element, $propertyPath)
    {
        if ($propertyPath[0] === '_' && $propertyPath !== '_hiddenInIndex') {
            return ObjectAccess::getPropertyPath($element, substr($propertyPath, 1));
        } else {
            return $element->getProperty($propertyPath);
        }
    }
}
