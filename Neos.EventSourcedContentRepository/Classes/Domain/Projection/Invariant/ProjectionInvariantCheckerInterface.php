<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Projection\Invariant;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Result;

interface ProjectionInvariantCheckerInterface
{

    /**
     * A is marked as hidden, so B and C should have incoming restriction edges.
     * This test should fail if e.g. in the example below, the restriction edge from A to C is missing.
     *
     * ┌─────┐
     * │  A  │━━┓
     * └─────┘  ┃
     *    │     ┃
     *    │     ┃
     * ┌─────┐  ┃
     * │  B  │◀━┛
     * └─────┘  ┃
     *    │
     *    │     ┃   <-- this Restriction Edge is missing.
     * ┌─────┐
     * │  C  │◀ ┛
     * └─────┘
     *
     * @return Result
     */
    public function restrictionsArePropagatedRecursively(): Result;

    /**
     * Checks that the restriction edges are connected at source (e.g. to "A") and at destination (e.g. to "B")
     *
     * ┌─────┐
     * │  A  │━━┓ <-- checks that A exists (for each restriction edge)
     * └─────┘  ┃
     *    │     ┃
     *    │     ┃
     * ┌─────┐  ┃
     * │  B  │◀━┛ <-- checks that B exists (for each restriction edge)
     * └─────┘
     *
     * @return Result
     */
    public function restrictionIntegrityValid(): Result;


    /**
     * Checks that the reference edges are connected at source (e.g. to "A") and at destination (e.g. to "B")
     *
     * ┌─────┐
     * │  A  │━━┓ <-- checks that A exists (for each reference edge)
     * └─────┘  ┃
     *    │     ┃
     *    │     ┃
     * ┌─────┐  ┃
     * │  B  │◀━┛ <-- checks that B exists (for each reference edge)
     * └─────┘
     *
     * @return Result
     */
    public function referenceIntegrityValid(): Result;

    /**
     * Checks that references originating at the same origin (and name) have different sortings.
     *
     * In the example, we have reference edges between A->B, and A->C; both named "ref" with sorting 10 (this is an error!)
     *
     * ┌─────┐   ref - 10 ┌─────┐
     * │  A  │───┬───────▶│  B  │
     * └─────┘   └──┐     └─────┘
     *              └──┐  ┌─────┐
     *                 └─▶│  C  │
     *           ref - 10 └─────┘
     *           !!ERROR!!
     *
     * @return Result
     */
    public function referenceSortingValid(): Result;

    /**
     * Checks that there are no cycles in the hierarchy between the nodes, in a given content stream identifier
     * and dimension space point.
     *
     * ┌─────┐
     * │  A  │◀─┐
     * └─────┘  │
     *    │     │
     *    ▼     │
     * ┌─────┐  │
     * │  B  │──┘
     * └─────┘
     *
     * @return Result
     */
    public function noCyclesExist(): Result;

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
     * @return Result
     */
    public function hierarchyIntegrityValid(): Result;


    /**
     * Checks that a node has incoming edges connected to a parent node
     *
     *  ───── <-- checks that the parent of "A" exists.
     *    │
     * ┌─────┐<-- checks that this edge exists (for each node)
     * │  A  │
     * └─────┘
     *
     * @return Result
     */
    public function everyNodeExceptRootNeedsAParent(): Result;

    /**
     * Checks that per subgraph (Dimension Space Point + Content Stream),
     * a Node Aggregate Identifier does not appear more than once.
     *
     *      ╱      ╲      <-- these two edges are from the same subgraph.
     *     ╱        ╲
     * ┌──▼──┐    ┌──▼──┐
     * │  B  │    │  B  │
     * └─────┘    └─────┘
     *
     * @return Result
     */
    public function nodeAggregateIdentifierIsUniquePerSubgraph(): Result;

    /**
     * Checks that per subgraph (Dimension Space Point + Content Stream),
     * a Node has exactly one parent.
     *
     * ╲     ╱   <-- these two edges are from the same subgraph.
     *  ╲   ╱
     * ┌─▼─▼─┐
     * │  A  │
     * └─────┘
     *
     * @return Result
     */
    public function nodeHasExactlyOneParentPerSubgraph(): Result;


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
    public function nodeTypeMustBeConsistentWithinNodeAggregateAndContentStream(): Result;


    /**
     * Child nodes cannot cover more dimension space points than their parents.
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
     *
     * @return Result
     */
    public function childNodesCannotCoverMoreDimensionSpacePointsThanTheirParents(): Result;

    /**
     * In a content stream, for every node aggregate, each origin dimension space point can only be
     * occupied at most once.
     *
     *      ╱      ╲  <-- the same content stream
     *     ╱        ╲
     * ┌──▼──┐    ┌──▼──┐
     * │A:ODSP    │A:ODSP  <-- two nodes have the same origin dimension space point!!
     * └─────┘    └─────┘
     *
     * @return Result
     */
    public function inContentStreamEveryOriginDimensionSpacePointIsUniquePerNodeAggregate(): Result;

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
     *
     * @return Result
     */
    public function sortingIsUniqueForEachParent(): Result;
}
