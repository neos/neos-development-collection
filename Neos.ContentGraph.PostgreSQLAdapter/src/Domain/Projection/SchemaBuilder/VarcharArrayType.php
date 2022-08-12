<?php

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class VarcharArrayType extends Type
{

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'varchar(255)[]';
    }

    public function getName()
    {
        return 'hypergraph_varchararray';
    }
}
