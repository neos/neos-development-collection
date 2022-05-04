<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;

/**
 * In the mid-term, this interface should be merged to the parent interface
 */
interface PropertyCollectionInterface extends
    \Neos\ContentRepository\SharedModel\Node\PropertyCollectionInterface
{
    public function serialized(): SerializedPropertyValues;
}
