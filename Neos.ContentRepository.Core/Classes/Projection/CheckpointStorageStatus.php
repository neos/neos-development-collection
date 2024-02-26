<?php

namespace Neos\ContentRepository\Core\Projection;

/**
 * @api
 */
final readonly class CheckpointStorageStatus
{
    /**
     * @param non-empty-string $details
     */
    private function __construct(
        public CheckpointStorageStatusType $type,
        public string $details,
    ) {
    }

    public static function ok(): self
    {
        /**
         * @phpstan-ignore-next-line php-stan doesn't support currently assertions on properties
         * https://github.com/phpstan/phpstan/issues/10463
         * Once implemented, we might be able to declare that $details is only a non-empty-string
         * if the CheckpointStorageStatusType is not OK
         */
        return new self(CheckpointStorageStatusType::OK, '');
    }

    /**
     * @param non-empty-string $details
     */
    public static function error(string $details): self
    {
        return new self(CheckpointStorageStatusType::ERROR, $details);
    }

    /**
     * @param non-empty-string $details
     */
    public static function setupRequired(string $details): self
    {
        return new self(CheckpointStorageStatusType::SETUP_REQUIRED, $details);
    }

    /**
     * @param non-empty-string $details
     */
    public function withDetails(string $details): self
    {
        return new self($this->type, $details);
    }
}
