<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Filter;

use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;

/**
 * Filter instances are used to filter nodes to be worked on during a migration.
 * A call to the matches() method is used to determine that.
 *
 * Settings given to a filter will be passed to accordingly named setters.
 */
interface NodeBasedFilterInterface
{
    /**
     * If the given node satisfies the filter constraints, true is returned.
     *
     * @param NodeInterface $node
     * @return boolean
     */
    public function matches(NodeInterface $node): bool;
}
