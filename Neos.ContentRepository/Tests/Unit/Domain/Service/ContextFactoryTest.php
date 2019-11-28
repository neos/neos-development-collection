<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility\Now;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Domain\Service\ContextFactory;

/**
 * Testcase for the ContextFactory
 *
 */
class ContextFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createMergesDefaultPropertiesBeforeSettingAnInstanceByIdentifier()
    {
        $contextFactory = new ContextFactory();
        $this->inject($contextFactory, 'now', new Now());

        $mockContentDimensionRepository = $this->createMock(ContentDimensionRepository::class);
        $mockContentDimensionRepository->expects(self::any())->method('findAll')->will(self::returnValue([]));
        $this->inject($contextFactory, 'contentDimensionRepository', $mockContentDimensionRepository);
        $this->inject($contextFactory, 'securityContext', $this->createMock(Context::class));

        $context1 = $contextFactory->create([]);
        $context2 = $contextFactory->create(['workspaceName' => 'live']);

        self::assertSame($context1, $context2, 'Contexts should be re-used');
    }
}
