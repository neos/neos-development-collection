<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\NodeTypeManager;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

class DefaultNodeTypeManagerFactory implements NodeTypeManagerFactoryInterface
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly ObjectManagerInterface $objectManager,
    )
    {
    }

    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings, array $nodeTypeManagerPreset): NodeTypeManager
    {
        return new NodeTypeManager(
            $this->configurationManager,
            $this->objectManager,
            $nodeTypeManagerPreset['options']['fallbackNodeTypeName']
        );
    }
}
