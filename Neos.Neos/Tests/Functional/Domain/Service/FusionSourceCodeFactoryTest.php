<?php

namespace Neos\Neos\Tests\Functional\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Domain\Service\FusionSourceCodeFactory;

class FusionSourceCodeFactoryTest extends FunctionalTestCase
{
    private FusionSourceCodeFactory $factory;

    public function setUp(): void
    {
        parent::setUp();
        $this->factory = $this->objectManager->get(FusionSourceCodeFactory::class);
    }

    /**
     * @test
     */
    public function sourceCodeCreatedFromAutoIncludes()
    {
        $sourceCodeCollection = $this->factory->createFromAutoIncludes();

        foreach ($sourceCodeCollection as $item) {
            if ($item->getFilePath() === "resource://Neos.Neos/Private/Fusion/Root.fusion") {
                self::assertTrue(true);
                return;
            }
        }

        self::fail("Expected Neos.Neos to be autoincluded.");
    }
}
