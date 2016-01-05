<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the ContextFactory
 *
 */
class ContextFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function createMergesDefaultPropertiesBeforeSettingAnInstanceByIdentifier()
    {
        $contextFactory = new \TYPO3\TYPO3CR\Domain\Service\ContextFactory();
        $this->inject($contextFactory, 'now', new \TYPO3\Flow\Utility\Now());

        $mockContentDimensionRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
        $mockContentDimensionRepository->expects($this->any())->method('findAll')->will($this->returnValue(array()));
        $this->inject($contextFactory, 'contentDimensionRepository', $mockContentDimensionRepository);
        $this->inject($contextFactory, 'securityContext', $this->getMock('TYPO3\Flow\Security\Context'));

        $context1 = $contextFactory->create(array());
        $context2 = $contextFactory->create(array('workspaceName' => 'live'));

        $this->assertSame($context1, $context2, 'Contexts should be re-used');
    }
}
