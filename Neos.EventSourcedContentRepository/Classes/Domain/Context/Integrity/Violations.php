<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Integrity;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\Integrity\Violation\ViolationInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class Violations implements \IteratorAggregate
{
    /**
     * @var array in the format ['<violationType>' => ['<violationHash>' => [<violation1>, <violation2>, ...], ...], ...]
     */
    private $violations;

    /**
     * @param ViolationInterface[] $violations
     */
    private function __construct(array $violations)
    {
        $this->violations = $violations;
    }

    public static function create(): self
    {
        return new static([]);
    }

    public function with(ViolationInterface $violation): self
    {
        $newInstance = new static($this->violations);
        $newInstance->violations[] = $violation;
        return $newInstance;
    }

    public function isEmpty(): bool
    {
        return $this->violations === [];
    }

    /**
     * @return \ArrayIterator|ViolationInterface[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->violations);
    }
}
