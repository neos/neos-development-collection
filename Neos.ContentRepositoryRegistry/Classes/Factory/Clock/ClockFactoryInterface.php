<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\Clock;

use Psr\Clock\ClockInterface;

interface ClockFactoryInterface
{
    public function build(): ClockInterface;
}
