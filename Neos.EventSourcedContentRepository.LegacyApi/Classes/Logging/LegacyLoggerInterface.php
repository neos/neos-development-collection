<?php

declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\Logging;

/**
 * - INFO log level should be used for logging deprecations by default
 * - WARNING log level should be used if we cannot provide useful fallback behavior.
 */
interface LegacyLoggerInterface extends \Psr\Log\LoggerInterface
{
}
