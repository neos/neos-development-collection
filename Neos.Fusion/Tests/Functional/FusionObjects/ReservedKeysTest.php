<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Core\FusionGlobals;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Fusion\Exception;

/**
 * Testcase for reserved Fusion keys
 *
 */
class ReservedKeysTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function usingReservedKeysThrowsException()
    {
        $this->expectException(Exception::class);
        $this->objectManager->get(RuntimeFactory::class)->createFromSourceCode(
            FusionSourceCodeCollection::fromFilePath(__DIR__ . '/Fixtures/ReservedKeysFusion/ReservedKeys.fusion'),
            FusionGlobals::empty()
        );
    }

    /**
     * @test
     */
    public function nonReservedKeysWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('reservedKeys');
        self::assertEquals($view->render(), ['__custom' => 1]);
    }
}
