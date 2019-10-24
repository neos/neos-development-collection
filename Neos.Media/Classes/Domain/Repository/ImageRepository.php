<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Repository;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\QueryBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ImageVariant;

/**
 * A repository for Images
 *
 * @Flow\Scope("singleton")
 */
class ImageRepository extends AssetRepository
{
    /**
     * Return raw data about existing assets and their variants
     *
     * @return array
     */
    public function findAssetIdentifiersWithVariants(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $assetIdentifiers = array_column($this->entityManager->createQuery(sprintf('SELECT i.Persistence_Object_Identifier  FROM %s i', $this->entityClassName))->getScalarResult(), 'Persistence_Object_Identifier');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('IDENTITY(v.originalAsset) AS assetIdentifier, v.presetIdentifier, v.presetVariantName')
            ->from(ImageVariant::class, 'v');

        $rawVariantData = $queryBuilder->getQuery()->getArrayResult();

        $variantData = [];
        foreach ($rawVariantData as $item) {
            if (!isset($variantData[$item['assetIdentifier']])) {
                $variantData[$item['assetIdentifier']] = [];
            }
            if ($item['presetIdentifier']) {
                $variantData[$item['assetIdentifier']][$item['presetIdentifier']][$item['presetVariantName']] = true;
            }
        }

        $result = [];
        foreach ($assetIdentifiers as $assetIdentifier) {
            $result[$assetIdentifier] = $variantData[$assetIdentifier] ?? [];
        }

        return $result;
    }
}
