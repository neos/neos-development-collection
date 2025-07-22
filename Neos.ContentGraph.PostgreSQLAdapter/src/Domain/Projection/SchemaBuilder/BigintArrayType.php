<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\PostgresTypes\AbstractArrayType;

/**
 * @internal
 */
class BigintArrayType extends AbstractArrayType
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'bigint_array';
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return '_int8';
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform)
    {
        return ['_int8'];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $result = parent::convertToPHPValue($value, $platform);

        return array_map('intval', $result);
    }
}
