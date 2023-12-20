<?php
namespace Neos\ContentRepositoryRegistry\Tests\Unit\Service\Fixture;

use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;

/**
 *
 */
readonly class FakeProjectionState implements ProjectionStateInterface
{
    public function __construct(public string $state) {}

    public function __toString(): string
    {
        return $this->state;
    }
}
