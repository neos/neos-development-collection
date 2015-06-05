<?php
namespace TYPO3\TYPO3CR\NodeTypePostprocessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * A NodeType postprocessor can be used in order to programmatically change the configuration of a node type
 * for example to provide dynamic properties.
 */
interface NodeTypePostprocessorInterface {

	/**
	 * Processes the given $nodeType (e.g. changes/adds properties depending on the NodeType configuration and the specified $options)
	 *
	 * @param NodeType $nodeType (uninitialized) The node type to process
	 * @param array $configuration The node type configuration to be processed
	 * @param array $options The processor options
	 * @return void
	 */
	public function process(NodeType $nodeType, array &$configuration, array $options);

}
