<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the Renderer Fusion object
 *
 */
class RendererTest extends AbstractFusionObjectTest
{
    public function assertRenderingWorks($path, $expectation)
    {
        $view = $this->buildView();
        $view->assign('cond', true);
        $view->setFusionPath($path);
        self::assertEquals($expectation, $view->render());
    }

    /**
     * @test
     */
    public function usingRendererWorks()
    {
        $this->assertRenderingWorks('renderer/default', 'result_of_renderer_prototyope');
    }

    /**
     * @test
     */
    public function rendererWinsOverType()
    {
        $this->assertRenderingWorks('renderer/withType', 'result_of_type_with_override');
    }

    /**
     * @test
     */
    public function rendererWinsOverRenderPath()
    {
        $this->assertRenderingWorks('renderer/withRenderPath', 'result_of_path_with_override');
    }
}
