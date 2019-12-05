<?php

namespace Neos\ContentRepository\Domain\Utility;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\QueryBuilder;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Exception;

class NodeDataUtility
{
    /**
     * Returns a subset of $nodes which are not flagged as removed.
     *
     * @param array $nodes NodeData including removed entries
     * @return array Only those NodeData instances which are not flagged as removed
     */
    public static function withoutRemovedNodes(array $nodes)
    {
        return array_filter($nodes, function (NodeData $node) {
            return !$node->isRemoved();
        });
    }

    /**
     * If $dimensions is not empty, adds join constraints to the given $queryBuilder
     * limiting the query result to matching hits.
     *
     * @param QueryBuilder $queryBuilder
     * @param array $dimensions
     * @return void
     */
    public static function addDimensionJoinConstraintsToQueryBuilder(QueryBuilder $queryBuilder, array $dimensions)
    {
        $count = 0;
        foreach ($dimensions as $dimensionName => $dimensionValues) {
            $dimensionAlias = 'd' . $count;
            $queryBuilder->andWhere(
                'EXISTS (SELECT ' . $dimensionAlias . ' FROM Neos\ContentRepository\Domain\Model\NodeDimension ' . $dimensionAlias . ' WHERE ' . $dimensionAlias . '.nodeData = n AND ' . $dimensionAlias . '.name = \'' . $dimensionName . '\' AND ' . $dimensionAlias . '.value IN (:' . $dimensionAlias . ')) ' .
                'OR NOT EXISTS (SELECT ' . $dimensionAlias . '_c FROM Neos\ContentRepository\Domain\Model\NodeDimension ' . $dimensionAlias . '_c WHERE ' . $dimensionAlias . '_c.nodeData = n AND ' . $dimensionAlias . '_c.name = \'' . $dimensionName . '\')'
            );
            $queryBuilder->setParameter($dimensionAlias, $dimensionValues);
            $count++;
        }
    }

    /**
     * Given an array with duplicate nodes (from different workspaces and dimensions) those are reduced to uniqueness (by node identifier)
     *
     * @param array $nodes NodeData result with multiple and duplicate identifiers (different nodes and redundant results for node variants with different dimensions)
     * @param array $workspaces
     * @param array $dimensions
     * @return array Array of unique node results indexed by identifier
     * @throws Exception\NodeException
     */
    public static function reduceNodeVariantsByWorkspacesAndDimensions(array $nodes, array $workspaces, array $dimensions)
    {
        $reducedNodes = [];

        $minimalDimensionPositionsByIdentifier = [];
        foreach ($nodes as $node) {
            /** @var NodeData $node */
            $nodeDimensions = $node->getDimensionValues();

            // Find the position of the workspace, a smaller value means more priority
            $workspaceNames = array_map(
                function (Workspace $workspace) {
                    return $workspace->getName();
                },
                $workspaces
            );
            $workspacePosition = array_search($node->getWorkspace()->getName(), $workspaceNames);
            if ($workspacePosition === false) {
                throw new Exception\NodeException(sprintf('Node workspace "%s" not found in allowed workspaces (%s), this could result from a detached workspace entity in the context.', $node->getWorkspace()->getName(), implode($workspaceNames, ', ')), 1413902143);
            }

            // Find positions in dimensions, add workspace in front for highest priority
            $dimensionPositions = [];

            // Special case for no dimensions
            if ($dimensions === []) {
                // We can just decide if the given node has no dimensions.
                $dimensionPositions[] = ($nodeDimensions === []) ? 0 : 1;
            }

            foreach ($dimensions as $dimensionName => $dimensionValues) {
                if (isset($nodeDimensions[$dimensionName])) {
                    foreach ($nodeDimensions[$dimensionName] as $nodeDimensionValue) {
                        $position = array_search($nodeDimensionValue, $dimensionValues);
                        $dimensionPositions[$dimensionName] = isset($dimensionPositions[$dimensionName]) ? min($dimensionPositions[$dimensionName],
                            $position) : $position;
                    }
                } else {
                    $dimensionPositions[$dimensionName] = isset($dimensionPositions[$dimensionName]) ? min($dimensionPositions[$dimensionName],
                        PHP_INT_MAX) : PHP_INT_MAX;
                }
            }
            $dimensionPositions[] = $workspacePosition;

            $identifier = $node->getIdentifier();
            // Yes, it seems to work comparing arrays that way!
            if (!isset($minimalDimensionPositionsByIdentifier[$identifier]) || $dimensionPositions < $minimalDimensionPositionsByIdentifier[$identifier]) {
                $reducedNodes[$identifier] = $node;
                $minimalDimensionPositionsByIdentifier[$identifier] = $dimensionPositions;
            }
        }

        return $reducedNodes;
    }

    /**
     * Returns an array that contains the given workspace and all base (parent) workspaces of it.
     *
     * @param Workspace $workspace
     * @return array
     */
    public static function collectWorkspaceAndAllBaseWorkspaces(Workspace $workspace)
    {
        $workspaces = [];
        while ($workspace !== null) {
            $workspaces[] = $workspace;
            $workspace = $workspace->getBaseWorkspace();
        }

        return $workspaces;
    }
}
