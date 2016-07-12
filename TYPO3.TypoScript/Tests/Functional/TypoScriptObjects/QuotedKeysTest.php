<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the TypoScript RawArray
 *
 */
class QuotedKeysTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function mulitpleKeysWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('quotedKeys/multipleKeys');
        $result = $view->render();

        $this->assertSame(count($result), 6);
        foreach ($result as $key => $value) {
            $this->assertSame($value, 1);
        }
    }

    /**
     * @test
     */
    public function singleQuotedWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('quotedKeys/single');
        $this->assertSame($view->render(), 1);
    }

    /**
     * @test
     */
    public function doubleQuotedWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('quotedKeys/double');
        $this->assertSame($view->render(), 1);
    }

    /**
     * @test
     */
    public function nestedQuotedWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('quotedKeys/nested/keys');
        $this->assertSame($view->render(), 1);
    }

    /**
     * @test
     */
    public function specialCharactersWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('quotedKeys/@special/_!');
        $this->assertSame($view->render(), 1);
    }

    /**
     * @test
     */
    public function prototypeAndQuotedKeysWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('quotedKeys/prototype/test');
        $this->assertSame($view->render(), 'X1');
    }
}
