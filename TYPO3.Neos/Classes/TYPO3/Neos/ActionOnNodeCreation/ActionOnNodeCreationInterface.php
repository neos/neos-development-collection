<?php
namespace TYPO3\Neos\ActionOnNodeCreation;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A Action On Node Creation creates child nodes or sets properties of the newly created node based on the given options
 */
interface ActionOnNodeCreationInterface {

	/**
	 * Checks if the current action can be executed for the given node and options
	 *
	 * @param NodeInterface $node
	 * @param array $options
	 * @return bool
	 */
	public function isActionable(NodeInterface $node, array $options);

	/**
	 * Execute the action (e.g. change properties or create child nodes)
	 *
	 * @param NodeInterface $node
	 * @param array $options
	 * @return void
	 */
	public function execute(NodeInterface $node, array $options);

}
