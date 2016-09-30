<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects\Fixtures\Helper;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\TypoScript;

class UtilityHelper implements ProtectedContextAwareInterface
{
    /**
     * @return void
     * @throws TypoScript\Exception
     */
    public function throwException()
    {
        throw new TypoScript\Exception('Just testing an exception', 1397118532);
    }

    /**
     * {@inheritdoc}
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
