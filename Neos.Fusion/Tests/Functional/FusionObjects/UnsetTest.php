<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
/**
 * Testcase for unsetting Fusion paths
 *
 */
class UnsetTest extends AbstractFusionObjectTestCase
{
    public static function unsetExamples()
    {
        return [
            ['valueUnset/inheritedPrototypePath', 'Baz'],
            ['valueUnset/nestedPrototype', 'FooBar'],
            ['valueUnset/simple', 'Bar'],
            ['valueUnset/topLevelPrototype', 'BarBazQuux']
        ];
    }

    #[DataProvider('unsetExamples')]
    #[Test]
    public function unsetWorks($path, $expected)
    {
        $view = $this->buildView();
        $view->setFusionPath($path);
        self::assertEquals($expected, $view->render());
    }
}
