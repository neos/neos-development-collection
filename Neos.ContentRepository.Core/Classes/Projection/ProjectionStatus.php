<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Doctrine\DBAL\Schema\SchemaDiff;

/**
 * @api
 */
final readonly class ProjectionStatus
{
    public function __construct(
        public ProjectionStatusType $type,
        public ?string $message
    ) {
    }

    public static function createOk(): self
    {
        return new self(ProjectionStatusType::OK, null);
    }

    /**
     * Infers the status based on a schema diff.
     *
     * Will not consider removed tables as changes {@see SchemaDiff::toSaveSql()}.
     */
    public static function createFromSchemaDiff(SchemaDiff $schemaDiff): self
    {
        if ($schemaDiff->newNamespaces !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires new namespace "%s" in schema.', current($schemaDiff->newNamespaces)));
        }
        if ($schemaDiff->newTables !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires new table "%s" in schema.', current($schemaDiff->newTables)->getName()));
        }
        if ($schemaDiff->changedTables !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires table "%s" to be changed in schema.', current($schemaDiff->changedTables)->name));
        }
        if ($schemaDiff->newSequences !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires new sequence "%s" in schema.', current($schemaDiff->newSequences)->getName()));
        }
        if ($schemaDiff->changedSequences !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires sequences "%s" to be changed in schema.', current($schemaDiff->changedSequences)->getName()));
        }
        return self::createOk();
    }
}
