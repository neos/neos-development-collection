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

use TYPO3\TYPO3CR\Domain\Model\Node;

/**
 * A happier node than the default node that can clap hands to show it!
 */
class HappyNode extends Node {

	/**
	 * @return string
	 */
	public function clapsHands() {
		return $this->getName() . ' claps hands!';
	}
}