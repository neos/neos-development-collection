<?php
namespace Neos\Media\Tests\Unit\ViewHelpers;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\Media\ViewHelpers\ImageViewHelper;

require_once(__DIR__ . '/../../../../../Framework/Neos.FluidAdaptor/Tests/Unit/ViewHelpers/ViewHelperBaseTestcase.php');

class ImageViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * var \Neos\Media\ViewHelpers\ImageViewHelper
     */
    protected $viewHelper;

    public function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new ImageViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function doNotThrowExceptionIfImageIsNull(): void
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $actualResult = $this->viewHelper->render();

        self::assertEquals('', $actualResult);
    }
}
