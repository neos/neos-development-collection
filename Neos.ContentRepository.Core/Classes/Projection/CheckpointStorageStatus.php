<?php

namespace Neos\ContentRepository\Core\Projection;

/**
 * @api
 */
final readonly class CheckpointStorageStatus
{
    public function __construct(
        public CheckpointStorageStatusType $type,
        public string $details,
    ) {
    }

    public static function ok(string $details = ''): self
    {
        return new self(CheckpointStorageStatusType::OK, $details);
    }

    public static function error(string $details): self
    {
        return new self(CheckpointStorageStatusType::ERROR, $details);
    }

    public static function setupRequired(string $details = ''): self
    {
        return new self(CheckpointStorageStatusType::SETUP_REQUIRED, $details);
    }
}
