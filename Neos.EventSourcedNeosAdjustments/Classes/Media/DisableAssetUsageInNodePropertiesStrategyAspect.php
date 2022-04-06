<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Media;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Neos\Domain\Model\Dto\AssetUsageInNodeProperties;

/**
 * The \Neos\Neos\Domain\Strategy\AssetUsageInNodePropertiesStrategy uses the NodeDataRepository in order to find asset
 * usages. Unfortunately it can't be disabled via Settings.yaml / Object.yaml.
 * Therefor we need to disable it effectively via AOP
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class DisableAssetUsageInNodePropertiesStrategyAspect
{

    /**
     * @Flow\Around("method(Neos\Neos\Domain\Strategy\AssetUsageInNodePropertiesStrategy->getUsageReferences())")
     * @param JoinPointInterface $joinPoint the join point
     * @return array<AssetUsageInNodeProperties>
     */
    public function disableGetUsageReferences(JoinPointInterface $joinPoint): array
    {
        return [];
    }
}
