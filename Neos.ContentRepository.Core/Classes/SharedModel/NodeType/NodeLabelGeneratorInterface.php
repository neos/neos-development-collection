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

namespace Neos\ContentRepository\SharedModel\NodeType;

use Neos\ContentRepository\Projection\ContentGraph\Node;

/**
 * Interface for rendering a node label string based on some strategy
 *
 * @api
 */
interface NodeLabelGeneratorInterface
{
    /**
     * Render a node label
     * @api
     */
    public function getLabel(Node $node): string;
}
