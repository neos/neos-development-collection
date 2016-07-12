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
 * Testcase for the TypoScript View
 *
 */
class ProcessorTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function basicProcessorsWork()
    {
        $this->assertMultipleTypoScriptPaths('Hello World foo', 'processors/newSyntax/basicProcessor/valueWithNested');
    }

    /**
     * @test
     */
    public function basicProcessorsBeforeValueWork()
    {
        $this->assertMultipleTypoScriptPaths('Hello World foo', 'processors/newSyntax/processorBeforeValue/valueWithNested');
    }

    /**
     * @test
     */
    public function extendedSyntaxProcessorsWork()
    {
        $this->assertMultipleTypoScriptPaths('Hello World foo', 'processors/newSyntax/extendedSyntaxProcessor/valueWithNested');
    }

    /**
     * Data Provider for processorsCanBeUnset
     *
     * @return array
     */
    public function dataProviderForUnsettingProcessors()
    {
        return array(
            array('processors/newSyntax/unset/simple'),
            array('processors/newSyntax/unset/prototypes1'),
            array('processors/newSyntax/unset/prototypes2'),
            array('processors/newSyntax/unset/nestedScope/prototypes3')
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForUnsettingProcessors
     */
    public function processorsCanBeUnset($path)
    {
        $view = $this->buildView();
        $view->setTypoScriptPath($path);
        $this->assertEquals('Foobaz', $view->render());
    }

    /**
     * @test
     */
    public function usingThisInProcessorWorks()
    {
        $this->assertTyposcriptPath('my value append', 'processors/newSyntax/usingThisInProcessor');
    }
}
