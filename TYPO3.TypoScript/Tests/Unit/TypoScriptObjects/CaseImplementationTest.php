<?php
namespace TYPO3\TypoScript\Tests\Unit\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
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
