<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\PostgresDbalClient;

/*
 * This file is part of the Neos.ContentRepositoryRegistry.PostgresDbalClient package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\SQLLogger;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The PostgreSQL-optimized Doctrine DBAL client adapter
 *
 * @Flow\Scope("singleton")
 */
class PostgresDbalClient implements PostgresDbalClientInterface
{
    /**
     * @Flow\InjectConfiguration(path="postgres.persistence.backendOptions")
     * @var array<string,mixed>
     */
    protected array $backendOptions = [];

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="persistence.doctrine.sqlLogger")
     * @var string|null
     */
    protected ?string $sqlLogger;

    protected Connection $connection;

    public function initializeObject(): void
    {
        $configuration = new Configuration();
        if (!empty($this->sqlLogger)) {
            $configuredSqlLoggerName = $this->sqlLogger;
            /** @var SQLLogger $configuredSqlLogger */
            $configuredSqlLogger = new $configuredSqlLoggerName();
            $configuration->setSQLLogger($configuredSqlLogger);
        }
        $this->connection = DriverManager::getConnection($this->backendOptions, $configuration);
    }

    public function getConnection(): Connection
    {
        if (!$this->connection->isConnected()) {
            $this->connection->connect();
        }

        return $this->connection;
    }
}
