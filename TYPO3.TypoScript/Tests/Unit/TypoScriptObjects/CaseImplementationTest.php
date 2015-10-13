<?php
namespace TYPO3\TypoScript\Tests\Unit\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Testcase for the Case object
 */
class CaseImplementationTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function ignoredPropertiesShouldNotBeUsedAsMatcher()
    {
        $path = 'page/body/content/main';
        $ignoredProperties = array('nodePath');

        $mockTsRuntime = $this->getMock('TYPO3\TypoScript\Core\Runtime', array(), array(), '', false);
        $mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) use ($path, $ignoredProperties) {
            $relativePath = str_replace($path . '/', '', $evaluatePath);
            switch ($relativePath) {
                case '__meta/ignoreProperties':
                    return $ignoredProperties;
            }
            return ObjectAccess::getProperty($that, $relativePath, true);
        }));

        $typoScriptObjectName = 'TYPO3.Neos:PrimaryContent';
        $renderer = new \TYPO3\TypoScript\TypoScriptObjects\CaseImplementation($mockTsRuntime, $path, $typoScriptObjectName);
        $renderer->setIgnoreProperties($ignoredProperties);

        $renderer['nodePath'] = 'main';
        $renderer['default'] = array(
            'condition' => 'true'
        );

        $mockTsRuntime->expects($this->once())->method('render')->with('page/body/content/main/default<TYPO3.TypoScript:Matcher>')->will($this->returnValue('rendered matcher'));

        $result = $renderer->evaluate();

        $this->assertEquals('rendered matcher', $result);
    }
}
