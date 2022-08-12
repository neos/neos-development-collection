<?php

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class JsonbType extends Type
{

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'jsonb';
    }

    public function getName()
    {
        return 'hypergraph_jsonb';
    }
}
