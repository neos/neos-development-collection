<?php
namespace TYPO3\TYPO3CR\Migration\Filters;

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
 * Filter instances are used to filter nodes to be worked on during a migration.
 * A call to the matches() method is used to determine that.
 *
 * Settings given to a transformation will be passed to accordingly named setters.
 */
interface FilterInterface {

	/**
	 * If the given node satisfies the filter constraints, TRUE is returned.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function matches(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node);

}
?>