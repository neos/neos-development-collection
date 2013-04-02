<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A container of properties which can be used as a template for generating new nodes.
 *
 * @api
 */
class NodeTemplate extends AbstractNode {

	/**
	 * Set the name of the node to $newName
	 *
	 * @param string $newName
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setName($newName) {
		if (!is_string($newName) || preg_match(self::MATCH_PATTERN_NAME, $newName) !== 1) {
			throw new \InvalidArgumentException('Invalid node name "' . $newName . '" (a node name must only contain characters, numbers and the "-" sign).', 1364290839);
		}
		$this->name = $newName;
	}

	/**
	 * Returns the name of this node
	 *
	 * @return string
	 * @api
	 */
	public function getName() {
		return $this->name;
	}

}
?>
