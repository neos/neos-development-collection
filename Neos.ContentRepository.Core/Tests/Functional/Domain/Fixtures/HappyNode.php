<?php
namespace Neos\ContentRepository\Tests\Functional\Domain\Fixtures;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Node;

/**
 * A happier node than the default node that can clap hands to show it!
 */
class HappyNode extends Node
{
    /**
     * @return string
     */
    public function clapsHands()
    {
        return $this->getName() . ' claps hands!';
    }
}
