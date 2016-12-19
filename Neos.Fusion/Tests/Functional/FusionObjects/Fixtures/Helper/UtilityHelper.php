<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects\Fixtures\Helper;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Fusion;

class UtilityHelper implements ProtectedContextAwareInterface
{
    /**
     * @return void
     * @throws Fusion\Exception
     */
    public function throwException()
    {
        throw new Fusion\Exception('Just testing an exception', 1397118532);
    }

    /**
     * {@inheritdoc}
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
