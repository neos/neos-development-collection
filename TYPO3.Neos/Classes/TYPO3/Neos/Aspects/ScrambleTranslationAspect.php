<?php
namespace TYPO3\Neos\Aspects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Utility\Unicode\Functions as UnicodeFunctions;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ScrambleTranslationAspect {

	/**
	 * @Flow\Around("setting(TYPO3.Neos.userInterface.scrambleTranslatedLabels) && method(TYPO3\Flow\I18n\Translator->translate.*())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return string A scrambled translation string
	 */
	public function scrambleTranslatedStrings(JoinPointInterface $joinPoint) {
		$translatedString = $joinPoint->getAdviceChain()->proceed($joinPoint);
		return str_repeat('#', UnicodeFunctions::strlen($translatedString));
	}

}