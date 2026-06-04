<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * @internal workaround to be used to mount the content graph in maria db while testing other projections in postgresql
 */
class DBALConnectionFactory
{
    /**
     * @param array<string,mixed> $options
     */
    public function create(array $options): Connection
    {
        return DriverManager::getConnection($options);
    }
}
