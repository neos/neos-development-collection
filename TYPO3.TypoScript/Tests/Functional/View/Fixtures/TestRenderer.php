<?php
namespace TYPO3\TypoScript\Tests\Functional\View\Fixtures;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\TypoScriptObjects\AbstractArrayTypoScriptObject;

/**
 * Test renderer
 */
class TestRenderer extends AbstractArrayTypoScriptObject
{
    /**
     * @return mixed
     */
    public function getTest()
    {
        return $this->tsValue('test');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function evaluate()
    {
        return 'X' . $this->getTest();
    }
}
