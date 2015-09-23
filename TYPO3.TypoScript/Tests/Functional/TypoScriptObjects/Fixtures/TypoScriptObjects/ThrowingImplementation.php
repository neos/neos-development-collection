<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects\Fixtures\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

class ThrowingImplementation extends \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject
{
    /**
     * @return boolean
     */
    protected function getShouldThrow()
    {
        return $this->tsValue('shouldThrow');
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate()
    {
        if ($this->getShouldThrow()) {
            throw new \TYPO3\TypoScript\Exception('Just testing an exception', 1396557841);
        }
        return 'It depends';
    }
}
