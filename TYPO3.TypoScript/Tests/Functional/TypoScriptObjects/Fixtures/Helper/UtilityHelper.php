<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects\Fixtures\Helper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

class UtilityHelper implements \TYPO3\Eel\ProtectedContextAwareInterface
{
    /**
     * @return void
     * @throws \TYPO3\TypoScript\Exception
     */
    public function throwException()
    {
        throw new \TYPO3\TypoScript\Exception('Just testing an exception', 1397118532);
    }

    /**
     * {@inheritdoc}
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
