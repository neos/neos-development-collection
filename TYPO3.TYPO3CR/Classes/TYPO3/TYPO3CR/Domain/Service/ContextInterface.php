<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Context Interface
 *
 */
interface ContextInterface {

	/**
	 * Returns the current workspace.
	 *
	 * @param boolean $createWorkspaceIfNecessary If enabled, creates a workspace with the configured name if it doesn't exist already
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace The workspace or NULL
	 * @api
	 */
	public function getWorkspace($createWorkspaceIfNecessary = TRUE);

	/**
	 * Returns the current date and time in form of a \DateTime
	 * object.
	 *
	 * If you use this method for getting the current date and time
	 * everywhere in your code, it will be possible to simulate a certain
	 * time in unit tests or in the actual application (for realizing previews etc).
	 *
	 * @return \DateTime The current date and time - or a simulated version of it
	 * @api
	 */
	public function getCurrentDateTime();

	/**
	 * Convenience method returns the root node for
	 * this context workspace.
	 *
	 * @return NodeInterface
	 * @api
	 */
	public function getRootNode();

	/**
	 * Returns a node specified by the given absolute path.
	 *
	 * @param string $path Absolute path specifying the node
	 * @return NodeInterface The specified node or NULL if no such node exists
	 * @api
	 */
	public function getNode($path);

	/**
	 * Get a node by identifier and this context
	 *
	 * @param string $identifier The identifier of a node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The node with the given identifier in this context, NULL if none was found
	 */
	public function getNodeByIdentifier($identifier);

	/**
	 * Finds all nodes lying on the path specified by (and including) the given
	 * starting point and end point.
	 *
	 * @param mixed $startingPoint Either an absolute path or an actual node specifying the starting point, for example /sites/mysite.com/
	 * @param mixed $endPoint Either an absolute path or an actual node specifying the end point, for example /sites/mysite.com/homepage/subpage
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> The nodes found between and including the given paths or an empty array of none were found
	 * @api
	 */
	public function getNodesOnPath($startingPoint, $endPoint);

	/**
	 * Tells if nodes which are usually invisible should be accessible through the Node API and queries
	 *
	 * @return boolean
	 * @see Node->filterNodeByContext()
	 * @api
	 */
	public function isInvisibleContentShown();

	/**
	 * Tells if nodes which have their "removed" flag set should be accessible through
	 * the Node API and queries
	 *
	 * @return boolean
	 * @see Node->filterNodeByContext()
	 * @api
	 */
	public function isRemovedContentShown();

	/**
	 * Tells if nodes which have access restrictions should be accessible through
	 * the Node API and queries even without the necessary roles / rights
	 *
	 * @return boolean
	 * @api
	 */
	public function isInaccessibleContentShown();

	/**
	 * An array of dimensions with ordered list of values to take into account when querying nodes
	 *
	 * @return array
	 */
	public function getDimensions();

	/**
	 * An array of dimensions with target values to set when updating nodes or creating new nodes
	 *
	 * This allows to have flexible translation modes where we can copy nodes from the dimensions in the context where
	 * it was fetched to other dimension values.
	 *
	 * @return array
	 */
	public function getTargetDimensions();

	/**
	 * Adopts a node from a (possibly) different context to this context by creating a compatible node variant that matches this context (if needed).
	 *
	 * @param NodeInterface $node The node with a different context. If the context of the given node is the same as this context the operation will have no effect.
	 * @return NodeInterface A new or existing node that matches this context
	 */
	public function adoptNode(NodeInterface $node);

	/**
	 * Returns the properties of the context
	 *
	 * @return array
	 */
	public function getProperties();

}
