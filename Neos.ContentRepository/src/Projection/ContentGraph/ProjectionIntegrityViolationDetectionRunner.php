<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Projection\ContentGraph;

use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;
use Neos\Error\Messages\Result;

/**
 * @api
 */
final class ProjectionIntegrityViolationDetectionRunner implements ContentRepositoryServiceInterface
{
    private ProjectionIntegrityViolationDetectorInterface $projectionIntegrityViolationDetector;

    public function __construct(ProjectionIntegrityViolationDetectorInterface $projectionIntegrityViolationDetector)
    {
        $this->projectionIntegrityViolationDetector = $projectionIntegrityViolationDetector;
    }

    public function run(): Result
    {
        $result = $this->projectionIntegrityViolationDetector->childNodeCoverageIsASubsetOfParentNodeCoverage();
        $result->merge($this->projectionIntegrityViolationDetector->allNodesCoverTheirOrigin());
        $result->merge($this->projectionIntegrityViolationDetector->nonRootNodesHaveParents());
        $result->merge($this->projectionIntegrityViolationDetector->hierarchyIntegrityIsProvided());
        $result->merge($this->projectionIntegrityViolationDetector->tetheredNodesAreNamed());
        $result->merge($this->projectionIntegrityViolationDetector->allNodesAreConnectedToARootNodePerSubgraph());
        $result->merge($this->projectionIntegrityViolationDetector->nodeAggregateIdentifiersAreUniquePerSubgraph());
        $result->merge($this->projectionIntegrityViolationDetector->allNodesHaveAtMostOneParentPerSubgraph());
        $result->merge($this->projectionIntegrityViolationDetector
            ->nodeAggregatesAreConsistentlyTypedPerContentStream());
        $result->merge($this->projectionIntegrityViolationDetector
            ->nodeAggregatesAreConsistentlyClassifiedPerContentStream());
        $result->merge($this->projectionIntegrityViolationDetector->referenceIntegrityIsProvided());
        $result->merge($this->projectionIntegrityViolationDetector->referencesAreDistinctlySorted());
        $result->merge($this->projectionIntegrityViolationDetector->restrictionIntegrityIsProvided());
        $result->merge($this->projectionIntegrityViolationDetector->restrictionsArePropagatedRecursively());
        $result->merge($this->projectionIntegrityViolationDetector->siblingsAreDistinctlySorted());

        return $result;
    }
}
