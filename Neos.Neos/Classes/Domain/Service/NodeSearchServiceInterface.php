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

use Neos\ContentRepository\Projection\Content\Nodes;

/**
 * Interface for the node search service for finding nodes based on a fulltext search
 */
interface NodeSearchServiceInterface
{
    /**
     * @param string $term
     * @param array $searchNodeTypes
     */
    public function findByProperties($term, array $searchNodeTypes): Nodes;
}
