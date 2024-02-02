<?php

namespace Neos\ContentRepository\Core\Projection;

/**
 * @api
 */
final readonly class ProjectionStatus
{
    public function __construct(
        public ProjectionStatusType $type,
        public string $details,
    ) {
    }

    public static function ok(): self
    {
        return new self(ProjectionStatusType::OK, '');
    }

    public static function error(string $details): self
    {
        return new self(ProjectionStatusType::ERROR, $details);
    }

    public static function setupRequired(string $details = ''): self
    {
        return new self(ProjectionStatusType::SETUP_REQUIRED, $details);
    }

    public static function replayRequired(string $details = ''): self
    {
        return new self(ProjectionStatusType::REPLAY_REQUIRED, $details);
    }
}
