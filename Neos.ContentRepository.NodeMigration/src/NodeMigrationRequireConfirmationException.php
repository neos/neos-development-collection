<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationSteps;

final class NodeMigrationRequireConfirmationException extends \DomainException
{
    public static function becauseStepsRequireConfirmation(TransformationSteps $transformationSteps): self
    {
        $additionalInformation = [];
        foreach ($transformationSteps as $transformationStep) {
            $additionalInformation[] = sprintf(
                'commands %s require confirmation: %s',
                join(',', array_map(fn (CommandInterface $command) => substr(strrchr($command::class, '\\') ?: '', 1), iterator_to_array($transformationStep->commands))),
                $transformationStep->confirmationReason,
            );
        }
        return new self(
            sprintf('%d warnings: %s', count($transformationSteps), join(";\n", $additionalInformation)),
            1742060622
        );
    }
}
