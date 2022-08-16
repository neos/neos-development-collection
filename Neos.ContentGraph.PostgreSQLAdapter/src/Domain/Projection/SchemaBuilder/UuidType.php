<?php

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class UuidType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'uuid';
    }

    public function getName()
    {
        return 'hypergraphuuid';
    }
}
