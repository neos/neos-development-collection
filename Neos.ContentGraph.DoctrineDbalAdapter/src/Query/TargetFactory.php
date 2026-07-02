<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Query;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Flowpack\QueryObjectBuilder\MySQL\Builder\Target;

final readonly class TargetFactory
{
    private function __construct()
    {
    }

    public static function forDbalPlatform(AbstractPlatform $platform): Target
    {
        // todo possibly determine target version by class?
        if ($platform instanceof MariaDBPlatform) {
            return Target::mariaDb();
        }
        return Target::mysql();
    }
}
