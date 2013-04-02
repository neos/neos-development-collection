<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\NodeTypePostprocessor\NodeTypePostprocessorInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * An example NodePostprocessor used by the NodesTests
 */
class TestNodePostprocessor implements NodeTypePostprocessorInterface {

	/**
	 * @param NodeType $nodeType The (uninitialized) node type to process
	 * @param array $configuration The configuration of the node type
	 * @param array $options The processor options
	 * @return void
	 */
	public function process(NodeType $nodeType, array &$configuration, array $options) {
		if ($nodeType->isOfType('TYPO3.TYPO3CR:TestingNodeTypeWithProcessor')) {
			$someOption = isset($options['someOption']) ? $options['someOption'] : '';
			$someOtherOption = isset($options['someOtherOption']) ? $options['someOtherOption'] : '';
			$configuration['properties']['test1']['defaultValue'] = sprintf('The value of "someOption" is "%s", the value of "someOtherOption" is "%s"', $someOption, $someOtherOption);
		}
	}

}
?>