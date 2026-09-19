<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandler\Commands;

final class TransformationStep
{
    private function __construct(
        public Commands $commands,
        public bool $requireConfirmation,
        public string $confirmationReason
    ) {
    }

    public static function createEmpty(): self
    {
        return new self(Commands::createEmpty(), false, '');
    }

    public static function fromCommand(CommandInterface $command): self
    {
        return new self(Commands::create($command), false, '');
    }

    public static function fromCommands(Commands $commands): self
    {
        return new self($commands, false, '');
    }

    public function withRequiredConfirmation(string $reason): self
    {
        if ($this->commands->isEmpty()) {
            throw new \InvalidArgumentException('Cannot make a noop step confirmation required.');
        }
        return new self(
            $this->commands,
            requireConfirmation: true,
            confirmationReason: $reason
        );
    }
}
