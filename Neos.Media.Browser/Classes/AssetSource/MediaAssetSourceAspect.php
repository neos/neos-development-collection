<?php
namespace Neos\Media\Browser\AssetSource;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Browser\AssetSource\AssetProxy\AssetProxy;
use Neos\Media\Browser\AssetSource\AssetProxy\HasRemoteOriginal;
use Neos\Media\Browser\Domain\Model\ImportedAsset;
use Neos\Media\Browser\Domain\Repository\ImportedAssetRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Media\Domain\Model\Asset;

/**
 * Introduce the "assetSourceIdentifier" property into the "Asset" domain model of the Media package.
 *
 * This is a preliminary solution which can be removed as soon as the Neos Media package provides that property in a
 * released version.
 *
 * @Flow\Aspect
 * @Flow\Introduce(pointcutExpression="class(Neos\Media\Domain\Model\Asset)", interfaceName="Neos\Media\Browser\AssetSource\MediaAssetSourceAware")
 */
class MediaAssetSourceAspect
{
    /**
     * @Flow\Introduce(pointcutExpression="class(Neos\Media\Domain\Model\Asset)")
     * @var string
     */
    public $assetSourceIdentifier = 'neos';

    /**
     * @Flow\Inject()
     * @var ImportedAssetRepository
     */
    protected $importedAssetRepository;

    /**
     * @Flow\InjectConfiguration(path="assetSources")
     * @var array
     */
    protected $assetSourcesConfiguration;

    /**
     * @Flow\Transient()
     * @var AssetSource[]
     */
    protected $assetSources = [];

    /**
     * @return void
     */
    public function initializeObject()
    {
        foreach ($this->assetSourcesConfiguration as $assetSourceIdentifier => $assetSourceConfiguration) {
            if (is_array($assetSourceConfiguration)) {
                $this->assetSources[$assetSourceIdentifier] = new $assetSourceConfiguration['assetSource']($assetSourceIdentifier, $assetSourceConfiguration['assetSourceOptions']);
            }
        }
    }

    /**
     * @param JoinPointInterface $joinPoint
     * @return string|null
     * @Flow\Around(pointcutExpression="method(Neos\Media\Domain\Model\Asset->getAssetSourceIdentifier())")
     */
    public function getAssetSourceIdentifierAdvice(JoinPointInterface $joinPoint)
    {
        $proxy = $joinPoint->getProxy();
        return $proxy->assetSourceIdentifier ?? null;
    }

    /**
     * @param JoinPointInterface $joinPoint
     * @Flow\Around(pointcutExpression="method(Neos\Media\Domain\Model\Asset->setAssetSourceIdentifier())")
     * @return void
     */
    public function setAssetSourceIdentifierAdvice(JoinPointInterface $joinPoint)
    {
        $proxy = $joinPoint->getProxy();
        $proxy->assetSourceIdentifier = $joinPoint->getMethodArgument('assetSourceIdentifier');
    }

    /**
     * @param JoinPointInterface $joinPoint
     * @return AssetSource|null
     * @Flow\Around(pointcutExpression="method(Neos\Media\Domain\Model\Asset->getAssetSource())")
     */
    public function getAssetSourceAdvice(JoinPointInterface $joinPoint)
    {
        $proxy = $joinPoint->getProxy();
        if (!$proxy instanceof MediaAssetSourceAware) {
            throw new \RuntimeException(sprintf('%s does not implement %s', get_class($proxy), MediaAssetSourceAware::class), 1516698457462);
        }

        $assetSourceIdentifier = $proxy->getAssetSourceIdentifier();
        if ($assetSourceIdentifier === null) {
            return null;
        }

        return $this->assetSources[$assetSourceIdentifier] ?? null;
    }

    /**
     * @param JoinPointInterface $joinPoint
     * @return AssetProxy|null
     * @Flow\Around(pointcutExpression="method(Neos\Media\Domain\Model\Asset->getAssetProxy())")
     */
    public function getAssetProxyAdvice(JoinPointInterface $joinPoint)
    {
        /** @var Asset $proxy */
        $proxy = $joinPoint->getProxy();
        if (!$proxy instanceof MediaAssetSourceAware) {
            throw new \RuntimeException(sprintf('%s does not implement %s', get_class($proxy), MediaAssetSourceAware::class), 1516699333650);
        }

        $assetSource = $proxy->getAssetSource();

        $importedAsset = $this->importedAssetRepository->findOneByLocalAssetIdentifier($proxy->getIdentifier());
        try {
            if ($importedAsset instanceof ImportedAsset) {
                    return $assetSource->getAssetProxyRepository()->getAssetProxy($importedAsset->getRemoteAssetIdentifier());
            } else {
                return $assetSource->getAssetProxyRepository()->getAssetProxy($proxy->getIdentifier());
            }
        } catch (AssetNotFoundException $e) {
            return null;
        } catch (AssetSourceConnectionException $e) {
            return null;
        }
    }
}
