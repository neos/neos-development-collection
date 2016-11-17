<?php
namespace TYPO3\Media\Tests\Unit\Domain\Model;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Media\Domain\Model\Video;

/**
 * Test case for the Video model
 */
class VideoTest extends UnitTestCase
{
    /**
     * @test
     */
    public function dimensionsDefaultToMinusOneOnConstruct()
    {
        $mockResource = $this->createMock('TYPO3\Flow\Resource\Resource');

        $video = new Video($mockResource);

        $this->assertEquals(-1, $video->getWidth());
        $this->assertEquals(-1, $video->getHeight());
    }
}
