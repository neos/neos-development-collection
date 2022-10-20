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

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\ValueObject\Configuration\VariantPreset;

/**
 * A repository for ImageVariants
 *
 * @Flow\Scope("singleton")
 */
class ImageVariantRepository extends AssetRepository
{
    /**
     * Returns array of ImageVariants with outdated presets
     *
     * @param VariantPreset[] $configuredPresets
     * @param bool $deleteOnlyFromGivenPresets
     * @param bool $filterCroppedVariants
     * @param int|null $limit
     * @return ImageVariant[]
     */
    public function findAllWithOutdatedPresets(array $configuredPresets, bool $deleteOnlyFromGivenPresets, bool $filterCroppedVariants, int $limit = null): array
    {
        $configuredIdentifiers = array_keys($configuredPresets);
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('iv')
            ->from(ImageVariant::class, 'iv')
            ->setMaxResults($limit);

        if (!$deleteOnlyFromGivenPresets) {
            /**
             * for completely outdated preset configurations
             *
             * EXAMPLE:
             *  - you have the identifiers Neos.Cool, Neos.Yeah (and there was a Neos.Awesome previously)
             * case 1:
             *  - the user want to delete variants from Neos.Yeah
             *  - condition will not be executed - deleteFromGivenPresets is true
             * case 2:
             *  - no preset to delete from configured
             *  - condition will be executed - whole Neos.Awesome will be added to query
             */
            $queryBuilder
                ->where('iv.presetIdentifier NOT IN (:configuredIdentifiers)')
                ->setParameter('configuredIdentifiers', $configuredIdentifiers);
        }

        if ($filterCroppedVariants) {
            // custom cropped variants will be saved with null value as presetIdentifier and presetVariantName
            $queryBuilder
                ->orWhere('iv.presetIdentifier IS NULL AND iv.presetVariantName IS NULL');
        }

        $i = 0;
        foreach ($configuredPresets as $presetIdentifier => $presetVariantNames) {
            $queryBuilder
                ->orWhere(
                    $queryBuilder->expr()->andX()
                        ->add($queryBuilder->expr()->eq('iv.presetIdentifier', sprintf(':presetIdentifier_%d', $i)))
                        ->add($queryBuilder->expr()->notIn('iv.presetVariantName', $presetVariantNames))
                )
                ->setParameter(sprintf('presetIdentifier_%d', $i), $presetIdentifier);
            $i++;
        }

        return $queryBuilder->getQuery()->execute();
    }
}
