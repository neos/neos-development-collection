<?php
namespace Neos\Neos\Domain\Service;

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
use Neos\ContentRepository\Domain\Service\Context;

interface NodeSearchResolverInterface
{
    /**
     * @param string[] $searchNodeTypes
     * @return NodeInterface[]
     */
    public function resolve(string $term, array $searchNodeTypes, Context $context, NodeInterface $startingPoint = null): array;

    public function matches(string $term, array $searchNodeTypes, Context $context, NodeInterface $startingPoint = null): bool;
}
