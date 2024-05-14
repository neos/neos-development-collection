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

namespace Neos\Neos\Domain\NodeLabel;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * @api to access the Node's label in PHP, in Fusion one can use ${q(node).label()}.
 */
interface NodeLabelGeneratorInterface
{
    /**
     * Render a node label
     * @api
     */
    public function getLabel(Node $node): string;
}
