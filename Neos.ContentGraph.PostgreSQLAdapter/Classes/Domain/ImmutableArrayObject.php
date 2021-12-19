<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain;

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

/**
 * An abstract immutable array object
 *
 * @Flow\Proxy(false)
 */
abstract class ImmutableArrayObject extends \ArrayObject
{
    protected function __construct(array $values)
    {
        parent::__construct($values);
    }

    public function offsetSet($key, $value): void
    {
        throw new \BadMethodCallException(get_class() . ' are immutable.', 1616604521);
    }

    public function offsetUnset($key): void
    {
        throw new \BadMethodCallException(get_class() . ' are immutable.', 1616604521);
    }

    public function append($value): void
    {
        throw new \BadMethodCallException(get_class() . ' are immutable.', 1616604521);
    }

    public function exchangeArray($array): array
    {
        throw new \BadMethodCallException(get_class() . ' are immutable.', 1616604521);
    }
}
