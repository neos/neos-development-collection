<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects\Fixtures\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class ThrowingImplementation extends AbstractFusionObject
{
    /**
     * @return boolean
     */
    protected function getShouldThrow()
    {
        return $this->fusionValue('shouldThrow');
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate()
    {
        if ($this->getShouldThrow()) {
            throw new Fusion\Exception('Just testing an exception', 1396557841);
        }
        return 'It depends';
    }
}
