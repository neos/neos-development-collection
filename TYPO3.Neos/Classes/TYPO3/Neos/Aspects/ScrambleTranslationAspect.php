<?php
namespace TYPO3\Neos\Aspects;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Utility\Unicode\Functions as UnicodeFunctions;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ScrambleTranslationAspect
{
    /**
     * @Flow\Around("setting(TYPO3.Neos.userInterface.scrambleTranslatedLabels) && method(TYPO3\Flow\I18n\Translator->translate.*())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return string A scrambled translation string
     */
    public function scrambleTranslatedStrings(JoinPointInterface $joinPoint)
    {
        $translatedString = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return str_repeat('#', UnicodeFunctions::strlen($translatedString));
    }
}
