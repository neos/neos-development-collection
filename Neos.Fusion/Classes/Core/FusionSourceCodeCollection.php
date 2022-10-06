<?php
declare(strict_types=1);

namespace Neos\Fusion\Core;

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

#[Flow\Proxy(false)]
class FusionSourceCodeCollection implements \IteratorAggregate, \Countable
{
    private array $fusionCodeCollection;

    public function __construct(FusionSourceCode ...$fusionCodeCollection)
    {
        $this->fusionCodeCollection = $fusionCodeCollection;
    }

    /** @param FusionSourceCode[] $fusionCodeCollection */
    public static function fromArray(array $fusionCodeCollection): self
    {
        return new self(...$fusionCodeCollection);
    }

    /** @return \ArrayIterator<int,FusionSourceCode>|FusionSourceCode[] */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->fusionCodeCollection);
    }

    public function count(): int
    {
        return count($this->fusionCodeCollection);
    }
}
