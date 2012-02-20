<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Case TypoScript Object
 *
 * We suggest the following matcher groups:
 *
 * Index 0-1000 -- RESERVED for special cases / override use
 * Index 1001 - 99 999 -- default TypoScript object definitions of extensions
 * Index > 100 000 -- default TypoScript object definitions of Phoenix
 *
 */
class CaseTsObject extends AbstractTsObject {

	/**
	 * A list of matchers
	 *
	 * @var array
	 */
	protected $matchers;

	/**
	 * @param array $matchers
	 */
	public function setMatchers($matchers) {
		$this->matchers = $matchers;
	}

	/**
	 * Execute each matcher condition, and if the condition matches, render the matcher type.
	 *
	 * @param mixed $node
	 * @return mixed
	 */
	public function evaluate($node) {
		$matchers = array_keys($this->tsValue('matchers'));
		asort($matchers);
		foreach ($matchers as $matcherName) {
			$evaluatedCondition = $this->tsValue('matchers.' . $matcherName . '.condition');
			if ($evaluatedCondition) {
				return $this->tsRuntime->render(
					sprintf('%s/element<%s>', $this->path, $this->tsValue('matchers.' . $matcherName . '.type'))
				);
			}
		}
		return 'No matcher found for ' . $node;
	}
}
?>