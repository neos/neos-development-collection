<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;


use Exception;
use JetBrains\PhpStorm\Internal\TentativeType;
use Traversable;

class Segments implements \IteratorAggregate
{
    /**
     * @var Segment[]
     */
    private array $segments;

    /**
     * @return Segment[]
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->segments);
    }
}
