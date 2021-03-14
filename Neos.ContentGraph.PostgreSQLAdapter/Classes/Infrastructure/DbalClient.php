<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Infrastructure;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
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
use Neos\Flow\Annotations as Flow;

/**
 * The Doctrine DBAL client adapter
 *
 * @Flow\Scope("singleton")
 */
class DbalClient
{
    /**
     * @Flow\InjectConfiguration(path="persistence.backendOptions")
     * @var array
     */
    protected array $backendOptions = [];

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="persistence.doctrine.sqlLogger")
     * @var string
     */
    protected string $sqlLogger;

    /**
     * @var Connection
     */
    protected Connection $connection;


    public function initializeObject(): void
    {
        $configuration = new Configuration();
        if (!empty($this->sqlLogger)) {
            $configuredSqlLogger = $this->sqlLogger;
            $configuration->setSQLLogger(new $configuredSqlLogger());
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
