<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Exception;

/**
 * @internal should never be thrown and expected
 */
class LocalDateTimeException extends \DomainException
{
    public static function becauseDateTimeIsLocal(\DateTimeImmutable $dateTime): self
    {
        return new self(
            sprintf('Date time is in local time: %s. Expected UTC +00:00', $dateTime->format(\DateTimeImmutable::ATOM)),
            1779531073
        );
    }
}
