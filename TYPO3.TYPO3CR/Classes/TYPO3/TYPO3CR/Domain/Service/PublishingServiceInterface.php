<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Exception\WorkspaceException;

/**
 * TYPO3CR Publishing Service Interface
 *
 * @api
 */
interface PublishingServiceInterface {

	/**
	 * Returns a list of nodes contained in the given workspace which are not yet published
	 *
	 * @param Workspace $workspace
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
	 */
	public function getUnpublishedNodes(Workspace $workspace);

	/**
	 * Returns the number of unpublished nodes contained in the given workspace
	 *
	 * @param Workspace $workspace
	 * @return integer
	 * @api
	 */
	public function getUnpublishedNodesCount(Workspace $workspace);

	/**
	 * Publishes the given node to the specified target workspace. If no workspace is specified, "live" is assumed.
	 *
	 * @param NodeInterface $node
	 * @param Workspace $targetWorkspace If not set the "live" workspace is assumed to be the publishing target
	 * @return void
	 * @api
	 */
	public function publishNode(NodeInterface $node, Workspace $targetWorkspace = NULL);

	/**
	 * Publishes the given nodes to the specified target workspace. If no workspace is specified, "live" is assumed.
	 *
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes The nodes to publish
	 * @param Workspace $targetWorkspace If not set the "live" workspace is assumed to be the publishing target
	 * @return void
	 * @api
	 */
	public function publishNodes(array $nodes, Workspace $targetWorkspace = NULL);

	/**
	 * Discards the given node.
	 *
	 * @param NodeInterface $node
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\WorkspaceException
	 * @api
	 */
	public function discardNode(NodeInterface $node);

	/**
	 * Discards the given nodes.
	 *
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes The nodes to discard
	 * @return void
	 * @api
	 */
	public function discardNodes(array $nodes);

	/**
	 * Discards all unpublished nodes of the given workspace.
	 *
	 * @param Workspace $workspace The workspace to flush, can't be the live workspace
	 * @return void
	 * @throws WorkspaceException
	 * @api
	 */
	public function discardAllNodes(Workspace $workspace);

}