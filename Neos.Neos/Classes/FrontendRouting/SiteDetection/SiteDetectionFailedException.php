<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\SiteDetection;

/**
 * This error will be thrown in {@see SiteDetectionResult::fromRequest()}
 */
class SiteDetectionFailedException extends \RuntimeException
{
}
