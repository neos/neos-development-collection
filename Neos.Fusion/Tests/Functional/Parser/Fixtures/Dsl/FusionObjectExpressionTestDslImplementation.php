<?php
namespace Neos\Fusion\Tests\Functional\Parser\Fixtures\Dsl;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\DslInterface;

class FusionObjectExpressionTestDslImplementation implements DslInterface
{
    public function transpile($code)
    {
        $config = json_decode($code, true);

        $objectName = $config['objectName'] ?: 'Neos.Fusion:Value';
        $attributes = $config['attributes'] ?: [];

        $result = $objectName . ' {' . chr(10);
        foreach ($attributes as $key => $value) {
            $result .= '    ' . $key . '= "' . $value . '"';
        }
        $result .= chr(10) . '}';
        return $result;
    }
}
