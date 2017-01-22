<?php
namespace Neos\Fusion\Tests\Functional\View\Fixtures;

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
use Neos\Fusion\FusionObjects\AbstractArrayFusionObject;

/**
 * Test renderer
 */
class TestRenderer extends AbstractArrayFusionObject
{
    /**
     * @return mixed
     */
    public function getTest()
    {
        return $this->fusionValue('test');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function evaluate()
    {
        return 'X' . $this->getTest();
    }
}
