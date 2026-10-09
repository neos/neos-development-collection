<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Processors\AssetRepositoryImportProcessor;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetRepository;
use PHPUnit\Framework\Assert;

trait AssetImportExportTrait
{
    /** @var array<string, string> resource file contents indexed by file name */
    private array $resourceContents = [];

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract protected function getObject(string $className): object;

    /**
     * Writes the given resource files to the export filesystem (as /Resources/<sha1 of content>)
     *
     * @Given using the following Resources:
     */
    public function usingTheFollowingResources(TableNode $resources): void
    {
        foreach ($resources->getHash() as $resourceData) {
            $this->resourceContents[$resourceData['file-name']] = $resourceData['content'];
            $this->crImportExportTrait_filesystem->write('/Resources/' . sha1($resourceData['content']), $resourceData['content']);
        }
    }

    /**
     * Writes the given Assets (one JSON object per line) as /Assets/<identifier>.json files to the export filesystem.
     *
     * The "resource.sha1" value can contain the placeholder "sha1(<file-name>)" that is replaced by the actual SHA1 hash
     * of the resource file with that name (see "using the following Resources")
     *
     * @Given /^using the following Assets:$/
     */
    public function usingTheFollowingAssets(PyStringNode $string): void
    {
        foreach (explode(PHP_EOL, $string->getRaw()) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $assetData = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            if (isset($assetData['resource']['sha1']) && preg_match('/^sha1\((.+)\)$/', $assetData['resource']['sha1'], $matches) === 1) {
                $assetData['resource']['sha1'] = sha1($this->getResourceContent($matches[1]));
            }
            $serializedAsset = SerializedAsset::fromArray($assetData);
            $this->crImportExportTrait_filesystem->write('/Assets/' . $serializedAsset->identifier . '.json', $serializedAsset->toJson());
        }
    }

    /**
     * Runs the {@see AssetRepositoryImportProcessor} on the export filesystem against the real AssetRepository and ResourceRepository
     *
     * @When I import the Assets
     */
    public function iImportTheAssets(): void
    {
        $assetImporter = new AssetRepositoryImportProcessor(
            $this->getObject(AssetRepository::class),
            $this->getObject(ResourceRepository::class),
            $this->getObject(ResourceManager::class),
            $this->getObject(PersistenceManagerInterface::class),
        );
        $this->runCrImportExportProcessors($assetImporter);
        $this->getObject(PersistenceManagerInterface::class)->clearState();
    }

    /**
     * @Then I expect the following Assets to exist:
     */
    public function iExpectTheFollowingAssetsToExist(TableNode $expectedAssets): void
    {
        $assetRepository = $this->getObject(AssetRepository::class);
        foreach ($expectedAssets->getHash() as $expectedAssetData) {
            $identifier = $expectedAssetData['identifier'];
            $actualAsset = $assetRepository->findByIdentifier($identifier);
            Assert::assertInstanceOf(Asset::class, $actualAsset, sprintf('Asset "%s" does not exist ', $identifier));

            $metadata = ['title', 'caption', 'copyrightNotice'];
            foreach ($metadata as $meta) {
                if (isset($expectedAssetData[$meta])) {
                    $getter = 'get' . ucfirst($meta);
                    Assert::assertEquals($expectedAssetData[$meta], $actualAsset->$getter());
                }
            }
        }
    }

    /**
     * @Then /^I expect (\d+) PersistentResources? for the Resource "([^"]*)"$/
     */
    public function iExpectPersistentResourcesForTheResource(int $expectedCount, string $fileName): void
    {
        $sha1 = sha1($this->getResourceContent($fileName));
        $actualResources = $this->getObject(ResourceRepository::class)->findBySha1($sha1);
        Assert::assertCount($expectedCount, $actualResources, sprintf('Expected %d PersistentResource(s) for Resource "%s" (SHA1 "%s")', $expectedCount, $fileName, $sha1));
    }

    private function getResourceContent(string $fileName): string
    {
        if (!isset($this->resourceContents[$fileName])) {
            throw new \RuntimeException(sprintf('Resource "%s" is not defined via "using the following Resources"', $fileName));
        }
        return $this->resourceContents[$fileName];
    }
}
