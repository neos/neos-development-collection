<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\SiteDetection;

/**
 * Neos was probably not setup yet.
 * No existent site entity will for example cause this issue.
 */
class SiteDetectionFailedException extends \RuntimeException
{
}
