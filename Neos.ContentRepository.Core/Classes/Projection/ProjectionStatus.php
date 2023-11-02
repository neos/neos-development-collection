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

    public static function createFromSchemaDiff(SchemaDiff $schemaDiff, bool $saveMode = false): self
    {
        if ($schemaDiff->newNamespaces !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires new namespace "%s" in schema.', current($schemaDiff->newNamespaces)));
        }
        // if ($schemaDiff->removedNamespaces !== []) {
        //     return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires namespace "%s" to be removed from schema.', current($schemaDiff->removedNamespaces)));
        // }
        if ($schemaDiff->newTables !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires new table "%s" in schema.', current($schemaDiff->newTables)->getName()));
        }
        if ($schemaDiff->changedTables !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires table "%s" to be changed in schema.', current($schemaDiff->changedTables)->name));
        }
        if ($schemaDiff->removedTables !== [] && $saveMode === false) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires table "%s" to be removed in schema.', current($schemaDiff->removedTables)->getName()));
        }
        if ($schemaDiff->newSequences !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires new sequence "%s" in schema.', current($schemaDiff->newSequences)->getName()));
        }
        if ($schemaDiff->changedSequences !== []) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires sequences "%s" to be changed in schema.', current($schemaDiff->changedSequences)->getName()));
        }
        if ($schemaDiff->removedSequences !== [] && $saveMode === false) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires sequences "%s" to be removed in schema.', current($schemaDiff->removedSequences)->getName()));
        }
        if ($schemaDiff->orphanedForeignKeys !== [] && $saveMode === false) {
            return new self(ProjectionStatusType::REQUIRES_SETUP, sprintf('Requires orphaned foreignKeys "%s" in schema.', current($schemaDiff->orphanedForeignKeys)->getName()));
        }
        return self::createOk();
    }
}
