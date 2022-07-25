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

namespace Neos\ContentRepository\Projection\ContentGraph;

use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;

/**
 * @extends \ArrayAccess<string,mixed>
 * @extends \IteratorAggregate<string,mixed>
 *
 * @api
 */
interface PropertyCollectionInterface extends \ArrayAccess, \IteratorAggregate
{
    /**
     * Retrieve the serialized property values
     */
    public function serialized(): SerializedPropertyValues;
}
