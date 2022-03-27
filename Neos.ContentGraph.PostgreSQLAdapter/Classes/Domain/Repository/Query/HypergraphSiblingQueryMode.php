<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
enum HypergraphSiblingQueryMode: string
{
    case MODE_ALL = 'all';
    case MODE_ONLY_PRECEDING = 'onlyPreceding';
    case MODE_ONLY_SUCCEEDING = 'onlySucceeding';

    public function renderCondition(): string
    {
        return match ($this) {
            self::MODE_ALL => '
    AND sn.relationanchorpoint = ANY(sh.childnodeanchors)',
            self::MODE_ONLY_PRECEDING => '
    AND sn.relationanchorpoint = ANY(sh.childnodeanchors[:(array_position(sh.childnodeanchors, sh.relationanchorpoint))])',
            self::MODE_ONLY_SUCCEEDING => '
    AND sn.relationanchorpoint = ANY(sh.childnodeanchors[(array_position(sh.childnodeanchors, sh.relationanchorpoint)):])'
        };
    }
}
