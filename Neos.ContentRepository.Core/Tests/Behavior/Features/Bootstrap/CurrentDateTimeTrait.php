<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DateTimeImmutable;

/**
 * The feature trait to simulate date and time
 */
trait CurrentDateTimeTrait
{
    protected ?DateTimeImmutable $currentDateAndTime = null;

    /**
     * @When the current date and time is :timestamp
     */
    public function theCurrentDateAndTimeIs(string $timestamp): void
    {
        $this->currentDateAndTime = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $timestamp);
    }

    public function getCurrentDateAndTime(): ?DateTimeImmutable
    {
        return $this->currentDateAndTime;
    }
}
