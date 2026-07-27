<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Neos\ContentRepository\Export\Processors\AssetRepositoryImportProcessor;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetRepository;
use PHPUnit\Framework\TestCase;

// FIXME, like ContentRepositoryMaintenanceCommandControllerTest this test should reside in
// Neos.ContentRepository.Export, but it requires a fully bootstrapped Flow (persistence,
// resource management, neos/media) which is only available in this test distribution
final class AssetRepositoryImportProcessorTest extends TestCase
{
    private AssetRepository $assetRepository;

    private PersistenceManagerInterface $persistenceManager;

    /** @var array<string> */
    private array $importedAssetIds = [];

    public function setUp(): void
    {
        $this->assetRepository = $this->getObject(AssetRepository::class);
        $this->persistenceManager = $this->getObject(PersistenceManagerInterface::class);
    }

    public function tearDown(): void
    {
        foreach ($this->importedAssetIds as $assetId) {
            $asset = $this->assetRepository->findByIdentifier($assetId);
            if ($asset !== null) {
                $this->assetRepository->remove($asset);
            }
        }
        $this->persistenceManager->persistAll();
    }

    /** @test */
    public function twoAssetsSharingTheSameResourceContentAreBothImported(): void
    {
        $fileContent = 'fake-jpeg-content-' . 'shared';
        $sha1 = sha1($fileContent);

        $files = new Filesystem(new InMemoryFilesystemAdapter());
        $files->write('/Resources/' . $sha1, $fileContent);
        foreach ([['duplicate-asset-1', 'first.jpg'], ['duplicate-asset-2', 'second.jpg']] as [$identifier, $filename]) {
            $this->importedAssetIds[] = $identifier;
            $files->write('/Assets/' . $identifier . '.json', json_encode([
                'identifier' => $identifier,
                'type' => 'IMAGE',
                'title' => 'Duplicate content test',
                'copyrightNotice' => '',
                'caption' => '',
                'assetSourceIdentifier' => 'neos',
                'resource' => [
                    'filename' => $filename,
                    'collectionName' => 'persistent',
                    'mediaType' => 'image/jpeg',
                    'sha1' => $sha1,
                ],
            ], JSON_THROW_ON_ERROR));
        }

        $processor = new AssetRepositoryImportProcessor(
            $this->assetRepository,
            $this->getObject(ResourceRepository::class),
            $this->getObject(ResourceManager::class),
            $this->persistenceManager,
        );

        $errors = [];
        $processor->run(new ProcessingContext($files, function (Severity $severity, string $message) use (&$errors) {
            if ($severity === Severity::ERROR) {
                $errors[] = $message;
            }
        }));

        self::assertSame([], $errors);

        $firstAsset = $this->assetRepository->findByIdentifier('duplicate-asset-1');
        $secondAsset = $this->assetRepository->findByIdentifier('duplicate-asset-2');
        self::assertInstanceOf(Asset::class, $firstAsset);
        self::assertInstanceOf(Asset::class, $secondAsset);

        // both assets reference their own resource instance (a resource can only belong to a single asset) …
        self::assertNotSame(
            $this->persistenceManager->getIdentifierByObject($firstAsset->getResource()),
            $this->persistenceManager->getIdentifierByObject($secondAsset->getResource())
        );
        // … while sharing the same content
        self::assertSame($sha1, $firstAsset->getResource()->getSha1());
        self::assertSame($sha1, $secondAsset->getResource()->getSha1());
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    private function getObject(string $className): object
    {
        return Bootstrap::$staticObjectManager->get($className);
    }
}
