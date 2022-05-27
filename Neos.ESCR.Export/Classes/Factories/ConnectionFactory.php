<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Factories;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * Factory for Doctrine connections
 */
#[Flow\Scope("singleton")]
class ConnectionFactory
{

    /**
     * NOTE: We inject the Doctrine ObjectManager in order to initialize the EntityManagerConfiguration::configureEntityManager
     * slot is invoked. Without this an exception 'Unknown column type "flow_json_array" requested' might be thrown
     *
     * @var EntityManagerInterface
     */
    #[Flow\Inject(lazy: false)]
    protected EntityManagerInterface $doctrineEntityManager;

    /**
     * @var array
     */
    #[Flow\InjectConfiguration(path: "persistence.backendOptions", package: "Neos.Flow")]
    protected array $defaultFlowDatabaseConfiguration;

    /**
     * @param array $options
     * @return Connection
     * @throws DbalException
     */
    public function create(array $connectionParams = []): Connection
    {
        $config = new Configuration();
        $connectionParams = Arrays::arrayMergeRecursiveOverrule($this->defaultFlowDatabaseConfiguration, $connectionParams);

        $connection = DriverManager::getConnection($connectionParams, $config);

        if (isset($options['mappingTypes']) && \is_array($options['mappingTypes'])) {
            foreach ($options['mappingTypes'] as $typeName => $typeConfiguration) {
                if (!Type::hasType($typeName)) {
                    Type::addType($typeName, $typeConfiguration['className']);
                }
                $connection->getDatabasePlatform()?->registerDoctrineTypeMapping($typeConfiguration['dbType'], $typeName);
            }
        }

        return $connection;
    }
}
