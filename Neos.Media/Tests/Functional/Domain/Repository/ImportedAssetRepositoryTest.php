<?php
namespace Neos\Media\Tests\Functional\Domain\Repository;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Media\Domain\Model\ImportedAsset;
use Neos\Media\Domain\Repository\ImportedAssetRepository;
use Neos\Media\Tests\Functional\AbstractTest;

class ImportedAssetRepositoryTest extends AbstractTest
{

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var ImportedAssetRepository
     */
    protected $importedAssetRepository;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->importedAssetRepository = $this->objectManager->get(ImportedAssetRepository::class);
    }

    /**
     * @test
     */
    public function findOneByAssetSourceIdentifierAndRemoteAssetIdentifier_selects_original_asset()
    {
        // To validate original is not found accidentally by implicit identifier sorting, this is fixated in the test (there's no foreign key constraint)
        $originalAssetIdentifier = 'd0105b8a-6f2c-47e3-b4a3-c2b5912e59e2';
        $assetVariant1Identifier = '7cfa67b6-c094-4391-85fd-82e0b8212e48';

        $importedAsset1 = new ImportedAsset('test-source', 'remote-id', $assetVariant1Identifier, new \DateTimeImmutable(), $originalAssetIdentifier);
        $importedAsset2 = new ImportedAsset('test-source', 'remote-id', $originalAssetIdentifier, new \DateTimeImmutable(), null);

        $this->importedAssetRepository->add($importedAsset1);
        $this->importedAssetRepository->add($importedAsset2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        /** @var ImportedAsset $importedAssetResult */
        $importedAssetResult = $this->importedAssetRepository->findOneByAssetSourceIdentifierAndRemoteAssetIdentifier('test-source', 'remote-id');

        $this->assertInstanceOf(ImportedAsset::class, $importedAssetResult);
        $this->assertEquals($originalAssetIdentifier, $importedAssetResult->getLocalAssetIdentifier());
    }
}
