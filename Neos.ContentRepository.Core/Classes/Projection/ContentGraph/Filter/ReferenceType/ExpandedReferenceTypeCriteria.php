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

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameSet;

/**
 * Implementation detail of {@see ReferenceTypeCriteria}, to be used inside implementations of ContentSubgraphInterface.
 *
 * @internal you want to use {@see ReferenceTypeCriteria} in public APIs.
 */
final readonly class ExpandedReferenceTypeCriteria
{
    private function __construct(
        public bool $isWildCardAllowed,
        public ReferenceTypeNameSet $explicitlyAllowedReferenceTypeNames,
        public ReferenceTypeNameSet $explicitlyDisallowedReferenceTypeNames
    ) {
    }

    public static function create(ReferenceTypeCriteria $referenceTypeCriteria, ReferenceTypeGraph $referenceTypeGraph): self
    {
        // in case there are no filters, we fall back to allowing every reference type.
        // Furthermore, if there are only negated filters,
        // we also fall back to allowing every node type (when the excludelist does not match)
        //
        // this logic can also be summarized to `$wildcardAllowed = $nodeTypeCriteria->explicitlyAllowedNodeTypeNames->isEmpty();`
        // see https://neos-project.slack.com/archives/C3MCBK6S2/p1695996578640689
        $nodeTypeCriteriaEmpty = $referenceTypeCriteria->explicitlyAllowedReferenceTypeNames->isEmpty()
            && $referenceTypeCriteria->explicitlyDisallowedReferenceTypeNames->isEmpty();
        $onlyNegatedFilters = $referenceTypeCriteria->explicitlyAllowedReferenceTypeNames->isEmpty()
            && !$referenceTypeCriteria->explicitlyDisallowedReferenceTypeNames->isEmpty();
        $wildcardAllowed = $nodeTypeCriteriaEmpty || $onlyNegatedFilters;

        return new self(
            isWildCardAllowed: $wildcardAllowed,
            explicitlyAllowedReferenceTypeNames: $referenceTypeGraph->expandNodeTypeSetToSubNodeTypes($referenceTypeCriteria->explicitlyAllowedReferenceTypeNames),
            explicitlyDisallowedReferenceTypeNames: $referenceTypeGraph->expandNodeTypeSetToSubNodeTypes($referenceTypeCriteria->explicitlyDisallowedReferenceTypeNames),
        );
    }

    public function matches(ReferenceTypeName $referenceTypeName): bool
    {
        // if $nodeTypeName is explicitly excluded, it is DENIED.
        foreach ($this->explicitlyDisallowedReferenceTypeNames as $disallowed) {
            if ($referenceTypeName === $disallowed) {
                return false;
            }
        }

        // if $nodeTypeName is explicitly ALLOWED.
        foreach ($this->explicitlyAllowedReferenceTypeNames as $allowed) {
            if ($referenceTypeName === $allowed) {
                return true;
            }
        }

        // otherwise, we return $wildcardAllowed.
        return $this->isWildCardAllowed;
    }
}
