<?php

declare(strict_types=1);

namespace Neos\Media\Tests\Unit\Domain\Service;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\Imagor\ImagorService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Neos\Media\Domain\Service\Imagor\ImagorService
 */
class ImagorServiceTest extends UnitTestCase
{
    private ImagorService $imagorService;

    private MockObject|ThumbnailConfiguration $thumbnailConfigurationMock;
    private MockObject|ImageInterface $imageMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imagorService = new ImagorService();

        $this->thumbnailConfigurationMock = $this->createMock(ThumbnailConfiguration::class);
        $this->imageMock = $this->createMock(ImageInterface::class);
    }

    public function testGetThumbnailUriAndSize(): void
    {
        $mock = $this->createMock(Thumbnail::class);

        $mock->expects(self::once())->method('getOriginalAsset')->willReturn($this->imageMock);

        $this->imagorService->getThumbnailUriAndSize($mock, $this->thumbnailConfigurationMock);
    }
}
