<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects;

use PHPUnit\Framework\Attributes\Test;

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
class RendererTest extends AbstractFusionObjectTestCase
{
    public function assertRenderingWorks($path, $expectation)
    {
        $view = $this->buildView();
        $view->assign('cond', true);
        $view->setFusionPath($path);
        self::assertEquals($expectation, $view->render());
    }

    #[Test]
    public function usingRendererWorks()
    {
        $this->assertRenderingWorks('renderer/default', 'result_of_renderer_prototyope');
    }

    #[Test]
    public function rendererWinsOverType()
    {
        $this->assertRenderingWorks('renderer/withType', 'result_of_type_with_override');
    }

    #[Test]
    public function rendererWinsOverRenderPath()
    {
        $this->assertRenderingWorks('renderer/withRenderPath', 'result_of_path_with_override');
    }
}
