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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\Error\Messages\Result;

/**
 * interface because different impls.
 *
 * @internal only API for custom content repository integrations
 */
interface ProjectionIntegrityViolationDetectorInterface
{
    public const ERROR_CODE_ORPHANED_NON_ROOT_NODE = 1597667433;
    public const ERROR_CODE_AMBIGUOUS_NODE_AGGREGATE_IN_SUBGRAPH = 1597671294;
    public const ERROR_CODE_CHILD_NODE_COVERAGE_IS_NO_SUBSET_OF_PARENT_NODE_COVERAGE = 1597735244;
    public const ERROR_CODE_NODE_AGGREGATE_IS_AMBIGUOUSLY_TYPED = 1597747062;
    public const ERROR_CODE_NODE_AGGREGATE_IS_AMBIGUOUSLY_CLASSIFIED = 1597825384;
    public const ERROR_CODE_NODE_IS_DISCONNECTED_FROM_THE_ROOT = 1597754245;
    public const ERROR_CODE_NODE_DOES_NOT_COVER_ITS_ORIGIN = 1597828607;
    public const ERROR_CODE_NODE_HAS_MISSING_SUBTREE_TAG = 1597837797;
    public const ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED = 1597909228;
    public const ERROR_CODE_SIBLINGS_ARE_AMBIGUOUSLY_SORTED = 1597910918;
    public const ERROR_CODE_REFERENCE_INTEGRITY_IS_COMPROMISED = 1597919585;
    public const ERROR_CODE_TETHERED_NODE_IS_UNNAMED = 1597923103;
    public const ERROR_CODE_NODE_HAS_MULTIPLE_PARENTS = 1597925698;

    /**
     * Checks that the hierarchy edges are connected at source (e.g. to "A") and at destination (e.g. to "B")
     *
     * ┌─────┐
     * │  A  │
     * └─────┘<-- checks that A exists (for each hierarchy edge)
     *    │
     *    │
     * ┌─────┐<-- checks that B exists (for each hierarchy edge)
     * │  B  │
     * └─────┘
     *
     * Additionally, checks that e.g. dimensionSpacePoint and dimensionSpacePointHash match; and same for
     * originDimensionSpacePoint and originDimensionSpacePointHash.
     *
     * Additionally, checks that a hierarchy edge (identified by source node aggregate id,
     * target node aggregate id, dimension space point and content stream id)
     * exists at most once.
     */
    public function hierarchyIntegrityIsProvided(): Result;

    /**
     * Two children of the same parent are not allowed to have the same sorting
     *
     *      ┌──────┐
     *      │  PAR │
     *      └──────┘
     *      ╱      ╲
     *   10╱        ╲10   <--- same sorting value here
     * ┌──▼──┐    ┌─────┐
     * │  A  │    │  B  │
     * └─────┘    └─────┘
     */
    public function siblingsAreDistinctlySorted(): Result;

    /**
     * All tethered nodes must have named parent hierarchy relations
     *
     * ┌─────┐
     * │  A  │
     * └─────┘
     *    │    <-- T is a tethered node, so the hierarchy relation must be named
     *    │
     * ┌─────┐
     * │  T  │
     * └─────┘
     */
    public function tetheredNodesAreNamed(): Result;

    /**
     * A is tagged with a subtree tag, so B and C should inherit that subtree tag (or explicitly have it set)
     * This test should fail if e.g. in the example below, C is missing the "foo" tag (<tag>* = explicitly set, <tag> = inherited):
     *
     * ┌────────────────────────┐
     * │           A            │
     * │                        │
     * │ SubtreeTags: foo*      │
     * │                        │
     * └───────────┬────────────┘
     *             │
     * ┌───────────┴────────────┐
     * │           B            │
     * │                        │
     * │ SubtreeTags: foo, bar* │
     * │                        │
     * └───────────┬────────────┘
     *             │
     * ┌───────────┴────────────┐
     * │           C            │
     * │                        │
     * │ SubtreeTags: bar       │ <-- is missing the inherited "foo" subtree tag
     * │                        │
     * └────────────────────────┘
     */
    public function subtreeTagsAreInherited(): Result;

    /**
     * Checks that the reference edges are connected at source (e.g. to "A") and at destination (e.g. to "B")
     *
     * This is violated if node aggregate B does not cover any DSPs that A does (or does not exist at all)
     *
     * ┌─────┐
     * │  A  │━━┓ <-- checks that A exists (for each reference edge)
     * └─────┘  ┃
     *    │     ┃
     *    │     ┃
     * ┌─────┐  ┃
     * │  B  │◀━┛ <-- checks that B exists (for each reference edge)
     * └─────┘
     */
    public function referenceIntegrityIsProvided(): Result;

    /**
     * Checks that references originating at the same origin (and name) have different sortings.
     *
     * In the example, we have reference edges between A->B, and A->C;
     * both named "ref" with sorting 10 (this is an error!)
     *
     * ┌─────┐   ref - 10 ┌─────┐
     * │  A  │───┬───────▶│  B  │
     * └─────┘   └──┐     └─────┘
     *              └──┐  ┌─────┐
     *                 └─▶│  C  │
     *           ref - 10 └─────┘
     *           !!ERROR!!
     */
    public function referencesAreDistinctlySorted(): Result;

    /**
     * Acyclicity check #1:
     * Checks that per subgraph all nodes are connected to a root node.
     *
     * ┌─────┐
     * │  A  │◀─┐   <-- neither A nor B are root nodes
     * └─────┘  │
     *    │     │
     *    ▼     │
     * ┌─────┐  │
     * │  B  │──┘
     * └─────┘
     */
    public function allNodesAreConnectedToARootNodePerSubgraph(): Result;

    /**
     * Acyclicity check #2:
     * Checks that per subgraph (Dimension Space Point + Content Stream),
     * all nodes have exactly one parent (except root nodes which have none).
     *
     * ╲     ╱   <-- these two edges are from the same subgraph.
     *  ╲   ╱
     * ┌─▼─▼─┐
     * │  A  │
     * └─────┘
     */
    public function allNodesHaveAtMostOneParentPerSubgraph(): Result;

    /**
     * Checks that a node has incoming edges connected to a parent node
     *
     *  ───── <-- checks that the parent of "A" exists.
     *    │
     * ┌─────┐<-- checks that this edge exists (for each node)
     * │  A  │
     * └─────┘
     */
    public function nonRootNodesHaveParents(): Result;

    /**
     * Checks that per subgraph (Dimension Space Point + Content Stream),
     * a Node Aggregate id does not appear more than once.
     *
     *      ╱      ╲      <-- these two edges are from the same subgraph.
     *     ╱        ╲
     * ┌──▼──┐    ┌──▼──┐
     * │  B  │    │  B  │
     * └─────┘    └─────┘
     */
    public function nodeAggregateIdsAreUniquePerSubgraph(): Result;

    /**
     * Checks that per content stream (NOT per subgraph), a Node Aggregate has
     * the same NodeType in all variants.
     *
     *      ╱      ╲  <-- these two edges are from the same CONTENT STREAM,
     *     ╱        ╲           but are of different Dimension Space Points.
     * ┌──▼──┐    ┌──▼──┐
     * │ B:T1│    │ B:T2│
     * └─────┘    └─────┘
     *    ^-----------^-------- B needs to have the same Node Type in this content stream.
     */
    public function nodeAggregatesAreConsistentlyTypedPerContentStream(): Result;

    /**
     * Checks that per content stream (NOT per subgraph), a Node Aggregate has
     * the same classification in all variants.
     *
     *         ╱           ╲  <-- these two edges are from the same CONTENT STREAM,
     *        ╱             ╲           but are of different Dimension Space Points.
     * ┌─────▼────┐    ┌─────▼─────┐
     * │ B:regular│    │ B:tethered│
     * └──────────┘    └───────────┘
     *    ^-----------^-------- B needs to have a consistent classification in this content stream.
     */
    public function nodeAggregatesAreConsistentlyClassifiedPerContentStream(): Result;

    /**
     * Child nodes must not cover dimension space points that their parents don't.
     *
     * ┌─────┐
     * │  A  │
     * └─────┘
     *    │    <-- between A and B, only "1 subgraph" relation exists.
     *    │
     * ┌─────┐
     * │  B  │
     * └─────┘
     *    │ ┃ <-- between B and C, two subgraphs exist (this cannot happen, as this must be smaller
     *    │ ┃     or equal than the parents.
     * ┌─────┐
     * │  C  │
     * └─────┘
     */
    public function childNodeCoverageIsASubsetOfParentNodeCoverage(): Result;

    /**
     * All nodes must at least cover their origin dimension space point
     *
     *    ┃ <-- the covered dimension space point is different from the originating
     *    ┃
     * ┌─────┐
     * │  N  │
     * └─────┘
     */
    public function allNodesCoverTheirOrigin(): Result;
}
