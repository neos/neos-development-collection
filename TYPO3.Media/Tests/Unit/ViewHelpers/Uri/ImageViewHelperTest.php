<?php
namespace TYPO3\Media\Tests\Unit\ViewHelpers\Uri;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\Media\ViewHelpers\ImageViewHelper;

require_once(__DIR__ . '/../../../../../../Framework/Neos.FluidAdaptor/Tests/Unit/ViewHelpers/ViewHelperBaseTestcase.php');

class ImageViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * var \TYPO3\Media\ViewHelpers\ImageViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = new ImageViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function doNotThrowExceptionIfImageIsNull()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render(null);

        $this->assertEquals('', $actualResult);
    }
}
