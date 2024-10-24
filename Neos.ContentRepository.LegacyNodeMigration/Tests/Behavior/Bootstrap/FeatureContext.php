<?php
declare(strict_types=1);

// @todo remove this require statement
require_once(__DIR__ . '/../../../../Neos.ContentRepository.Export/Tests/Behavior/Features/Bootstrap/CrImportExportTrait.php');

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Neos\Behat\FlowBootstrapTrait;
use Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap\CrImportExportTrait;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinPyStringNodeBasedNodeTypeManagerFactory;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinTableNodeBasedContentDimensionSourceFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Export\Asset\AssetExporter;
use Neos\ContentRepository\Export\Asset\AssetLoaderInterface;
use Neos\ContentRepository\Export\Asset\ResourceLoaderInterface;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedResource;
use Neos\ContentRepository\LegacyNodeMigration\Processors\AssetExportProcessor;
use Neos\ContentRepository\LegacyNodeMigration\Processors\EventExportProcessor;
use Neos\ContentRepository\LegacyNodeMigration\Processors\SitesExportProcessor;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\PersistentResource;
use PHPUnit\Framework\MockObject\Generator as MockGenerator;

/**
 * Features context
 */
class FeatureContext implements Context
{
    use FlowBootstrapTrait;
    use CRTestSuiteTrait;
    use CRBehavioralTestsSubjectProvider;
    use CrImportExportTrait;

    protected $isolated = false;

    private array $nodeDataRows = [];
    private array $siteDataRows = [];
    private array $domainDataRows = [];
    /** @var array<PersistentResource> */
    private array $mockResources = [];
    /** @var array<SerializedAsset|SerializedImageVariant> */
    private array $mockAssets = [];
    private ContentRepository $contentRepository;

    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function __construct()
    {
        self::bootstrapFlow();
        $this->contentRepositoryRegistry = $this->getObject(ContentRepositoryRegistry::class);

        $this->setupCrImportExportTrait();
    }

    /**
     * @When I have the following node data rows:
     */
    public function iHaveTheFollowingNodeDataRows(TableNode $nodeDataRows): void
    {
        $this->nodeDataRows = array_map(static function (array $row) {
            return [
                'path' => $row['Path'],
                'parentpath' => implode('/', array_slice(explode('/', $row['Path']), 0, -1)) ?: '/',
                'identifier' => $row['Identifier'] ?? NodeAggregateId::create()->value,
                'nodetype' => $row['Node Type'] ?? 'unstructured',
                'properties' => !empty($row['Properties']) ? $row['Properties'] : '{}',
                'dimensionvalues' => !empty($row['Dimension Values']) ? $row['Dimension Values'] : '{}',
                'hiddeninindex' => $row['Hidden in index'] ?? '0',
                'hiddenbeforedatetime' =>  !empty($row['Hidden before DateTime']) ? ($row['Hidden before DateTime']): null,
                'hiddenafterdatetime' =>  !empty($row['Hidden after DateTime']) ? ($row['Hidden after DateTime']) : null,
                'hidden' => $row['Hidden'] ?? '0',
            ];
        }, $nodeDataRows->getHash());
    }

    /**
     * @When I run the event migration
     * @When I run the event migration for workspace :workspace
     */
    public function iRunTheEventMigration(string $workspace = null): void
    {
        $nodeTypeManager = $this->currentContentRepository->getNodeTypeManager();
        $propertyMapper = $this->getObject(PropertyMapper::class);

        // HACK to access the property converter
        $propertyConverterAccess = new class implements ContentRepositoryServiceFactoryInterface {
            public PropertyConverter|null $propertyConverter;
            public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
            {
                $this->propertyConverter = $serviceFactoryDependencies->propertyConverter;
                return new class implements ContentRepositoryServiceInterface
                {
                };
            }
        };
        $this->getContentRepositoryService($propertyConverterAccess);

        $eventExportProcessor = new EventExportProcessor(
            $nodeTypeManager,
            $propertyMapper,
            $propertyConverterAccess->propertyConverter,
            $this->currentContentRepository->getVariationGraph(),
            $this->getObject(EventNormalizer::class),
            $this->nodeDataRows
        );

        $this->runCrImportExportProcessors($eventExportProcessor);
    }

    /**
     * @Given the following PersistentResources exist
     */
    public function theFollowingPersistentResourcesExist(TableNode $resources): void
    {
        foreach ($resources->getHash() as $resourceData) {
            $mockResource = (new MockGenerator())->getMock(PersistentResource::class, [], [], '', false);
            $mockResource->method('getFilename')->willReturn($resourceData['filename'] ?? 'filename');
            $mockResource->method('getCollectionName')->willReturn($resourceData['collectionName'] ?? 'persistent');
            $mockResource->method('getMediaType')->willReturn($resourceData['mediaType'] ?? 'image/jpeg');

            $contents = $resourceData['contents'] ?? $resourceData['identifier'];
            $mockResource->method('getSha1')->willReturn(sha1($contents));
            $stream = fopen('php://memory', 'rb+');
            fwrite($stream, $contents);
            rewind($stream);
            $mockResource->method('getStream')->willReturn($stream);
            $this->mockResources[$resourceData['identifier']] = $mockResource;
        }
    }

    /**
     * @Given the following Assets exist
     */
    public function theFollowingAssetsExist(TableNode $images): void
    {
        foreach ($images->getHash() as $assetData) {

            if (!isset($this->mockResources[$assetData['resourceId']])) {
                throw new \RuntimeException(sprintf('Resource id "%s" referenced in asset "%s" does not exist', $assetData['resourceId'], $assetData['identifier']));
            }
            $assetData['resource'] = SerializedResource::fromResource($this->mockResources[$assetData['resourceId']])->jsonSerialize();
            unset($assetData['resourceId']);
            $mockAsset = SerializedAsset::fromArray($assetData);
            $this->mockAssets[$mockAsset->identifier] = $mockAsset;
        }
    }

    /**
     * @When I run the asset migration
     */
    public function iRunTheAssetMigration(): void
    {
        $nodeTypeManager = $this->currentContentRepository->getNodeTypeManager();
        $mockResourceLoader = new class ($this->mockResources) implements ResourceLoaderInterface
        {
            /**
             * @param array<PersistentResource> $mockResources
             */
            public function __construct(private array $mockResources)
            {
            }

            public function getStreamBySha1(string $sha1)
            {
                foreach ($this->mockResources as $mockResource) {
                    if ($mockResource->getSha1() === $sha1) {
                        return $mockResource->getStream();
                    }
                }
                throw new \InvalidArgumentException(sprintf('Mock resource with SHA1 "%s" does not exist', $sha1), 1659532905);
            }
        };

        $mockAssetLoader = new class ($this->mockAssets) implements AssetLoaderInterface {
            /**
             * @param array<SerializedAsset|SerializedImageVariant> $mockAssets
             */
            public function __construct(private array $mockAssets)
            {
            }

            public function findAssetById(string $assetId): SerializedAsset|SerializedImageVariant
            {
                if (!isset($this->mockAssets[$assetId])) {
                    throw new \InvalidArgumentException(sprintf('Failed to find mock asset with id "%s"', $assetId));
                }
                return $this->mockAssets[$assetId];
            }
        };

        $assetExporter = new AssetExporter($this->crImportExportTrait_filesystem, $mockAssetLoader, $mockResourceLoader);
        $migration = new AssetExportProcessor($nodeTypeManager, $assetExporter, $this->nodeDataRows);
        $this->runCrImportExportProcessors($migration);
    }

    /**
     * @When I have the following site data rows:
     */
    public function iHaveTheFollowingSiteDataRows(TableNode $siteDataRows): void
    {
        $this->siteDataRows = array_map(
            fn (array $row) => array_map(
                fn(string $value) => json_decode($value, true),
                $row
            ),
            $siteDataRows->getHash()
        );
    }

    /**
     * @When I have the following domain data rows:
     */
    public function iHaveTheFollowingDomainDataRows(TableNode $domainDataRows): void
    {
        $this->domainDataRows = array_map(static function (array $row) {
            return array_map(
                fn(string $value) => json_decode($value, true),
                $row
            );
        }, $domainDataRows->getHash());
    }

    /**
     * @When I run the site migration
     */
    public function iRunTheSiteMigration(): void
    {
        $migration = new SitesExportProcessor($this->siteDataRows, $this->domainDataRows);
        $this->runCrImportExportProcessors($migration);
    }

    /** ---------------------------------- */

    protected function getContentRepositoryService(
        ContentRepositoryServiceFactoryInterface $factory
    ): ContentRepositoryServiceInterface {
        return $this->contentRepositoryRegistry->buildService(
            $this->currentContentRepository->id,
            $factory
        );
    }

    protected function createContentRepository(
        ContentRepositoryId $contentRepositoryId
    ): ContentRepository {
        $this->contentRepositoryRegistry->resetFactoryInstance($contentRepositoryId);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        GherkinTableNodeBasedContentDimensionSourceFactory::reset();
        GherkinPyStringNodeBasedNodeTypeManagerFactory::reset();

        return $contentRepository;
    }
}
