<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\DoctrineMigration;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;


/**
 * @internal
 */
class ProjectionDoctrineMigrationHelper {

    /**
     * @return ContentRepositoryId[]
     */
    public static function configuredContentRepositoryIds() {
        $configurationManager = Bootstrap::$staticObjectManager->get(ConfigurationManager::class);
        $contentRepositoryRegistrySettings = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.ContentRepositoryRegistry'
        );

        $result = [];
        foreach ($contentRepositoryRegistrySettings['contentRepositories'] as $contentRepositoryId => $contentRepositoryConfig) {
            $result[] = ContentRepositoryId::fromString($contentRepositoryId);
        }
        return $result;
    }
}
