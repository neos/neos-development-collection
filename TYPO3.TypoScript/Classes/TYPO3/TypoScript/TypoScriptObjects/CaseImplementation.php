<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Case TypoScript Object
 *
 * The "case" TypoScript object renders its children in order. The first
 * result which is not MATCH_NORESULT is returned.
 *
 * Often, this TypoScript object is used together with the "Matcher" TypoScript
 * object; and all its children are by-default interpreted as "Matcher" TypoScript
 * objects if no others are specified.
 */
class CaseImplementation extends ArrayImplementation {

	/**
	 * This constant should be returned by individual matchers if the matcher
	 * did not match.
	 *
	 * You should not rely on the contents or type of this constant.
	 */
	const MATCH_NORESULT = '_____________NO_MATCH_RESULT_____________';

	/**
	 * Execute each matcher until the first one matches
	 *
	 * @return mixed
	 */
	public function evaluate() {
		$matcherKeys = $this->sortNestedTypoScriptKeys();

		foreach ($matcherKeys as $matcherName) {
			$renderedMatcher = NULL;

			if (isset($this->subElements[$matcherName]['__objectType'])) {
					// object type already set, so no need to set it
				$renderedMatcher = $this->tsRuntime->render(
					sprintf('%s/%s', $this->path, $matcherName)
				);
			} elseif (!is_array($this->subElements[$matcherName])) {
				throw new \TYPO3\TypoScript\Exception\UnsupportedObjectTypeAtPathException('"Case" TypoScript object only supports nested TypoScript objects; no simple values.', 1372668062);
			} elseif (isset($this->subElements[$matcherName]['__eelExpression'])) {
				throw new \TYPO3\TypoScript\Exception\UnsupportedObjectTypeAtPathException('"Case" TypoScript object only supports nested TypoScript objects; no Eel expressions.', 1372668077);
			} else {
					// No object type has been set, so we're using TYPO3.TypoScript:Matcher as fallback
				$renderedMatcher = $this->tsRuntime->render(
					sprintf('%s/%s<TYPO3.TypoScript:Matcher>', $this->path, $matcherName)
				);
			}

			if ($renderedMatcher !== self::MATCH_NORESULT) {
				return $renderedMatcher;
			}
		}

		return NULL;
	}
}
?>