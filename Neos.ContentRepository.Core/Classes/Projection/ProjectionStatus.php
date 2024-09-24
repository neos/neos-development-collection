<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

/**
 * @api
 */
final readonly class ProjectionStatus
{
    private function __construct(
        public ProjectionStatusType $type,
        public string $details,
    ) {
    }

    public static function ok(): self
    {
        return new self(ProjectionStatusType::OK, '');
    }

    /**
     * @param non-empty-string $details
     */
    public static function error(string $details): self
    {
        return new self(ProjectionStatusType::ERROR, $details);
    }

    /**
     * @param non-empty-string $details
     */
    public static function setupRequired(string $details): self
    {
        return new self(ProjectionStatusType::SETUP_REQUIRED, $details);
    }

    /**
     * @param non-empty-string $details
     */
    public static function replayRequired(string $details): self
    {
        return new self(ProjectionStatusType::REPLAY_REQUIRED, $details);
    }

    /**
     * @param non-empty-string $details
     */
    public function withDetails(string $details): self
    {
        return new self($this->type, $details);
    }
}
