<?php
namespace Neos\Media\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Video;

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
        $mockResource = $this->createMock(PersistentResource::class);

        $video = new Video($mockResource);

        self::assertEquals(-1, $video->getWidth());
        self::assertEquals(-1, $video->getHeight());
    }
}
