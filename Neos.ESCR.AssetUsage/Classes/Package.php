<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage;

use Neos\ESCR\AssetUsage\Projector\AssetUsageRepository;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;

class Package extends BasePackage
{

    public function boot(Bootstrap $bootstrap): void
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect(AssetService::class, 'assetRemoved', function (AssetInterface $asset) use ($bootstrap) {
            /** @var AssetUsageRepository $assetUsageRepository */
            $assetUsageRepository = $bootstrap->getObjectManager()->get(AssetUsageRepository::class);
            /** @var PersistenceManagerInterface $persistenceManager */
            $persistenceManager = $bootstrap->getObjectManager()->get(PersistenceManagerInterface::class);
            $assetIdentifier = $persistenceManager->getIdentifierByObject($asset);
            if (is_string($assetIdentifier)) {
                $assetUsageRepository->removeAsset($assetIdentifier);
            }
        });
    }
}
