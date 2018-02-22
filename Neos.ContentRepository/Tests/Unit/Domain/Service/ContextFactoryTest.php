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
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility\Now;
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

        $mockContentDimensionSource = $this->createMock(Dimension\ContentDimensionSourceInterface::class);
        $mockContentDimensionSource->expects($this->any())->method('getContentDimensionsOrderedByPriority')->will($this->returnValue([]));
        $this->inject($contextFactory, 'contentDimensionSource', $mockContentDimensionSource);
        $this->inject($contextFactory, 'securityContext', $this->createMock(Context::class));

        $context1 = $contextFactory->create(array());
        $context2 = $contextFactory->create(array('workspaceName' => 'live'));

        $this->assertSame($context1, $context2, 'Contexts should be re-used');
    }
}
