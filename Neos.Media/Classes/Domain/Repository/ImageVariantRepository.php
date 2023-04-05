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
use Neos\Media\Domain\Service\AssetVariantGenerator;

/**
 * @Flow\Scope("singleton")
 */
class ImageVariantRepository extends AssetRepository
{
    /**
     * @Flow\Inject
     * @var AssetVariantGenerator
     */
    protected $assetVariantGenerator;

    /**
     * @return ImageVariant[]
     */
    public function findVariantsByIdentifierAndVariantName(string $identifier, string $variantName, int $limit = null): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('iv')
            ->from(ImageVariant::class, 'iv')
            ->setMaxResults($limit);

        $queryBuilder
            ->where('iv.presetIdentifier = (:configuredIdentifier)')
            ->setParameter('configuredIdentifier', $identifier);

        $queryBuilder
            ->andWhere('iv.presetVariantName = (:configuredVariantName)')
            ->setParameter('configuredVariantName', $variantName);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @return ImageVariant[]
     */
    public function findAllWithOutdatedPresets(int $limit = null): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('iv')
            ->from(ImageVariant::class, 'iv')
            ->setMaxResults($limit);

        $configuredPresets = $this->assetVariantGenerator->getVariantPresets();
        $configuredIdentifiers = array_keys($configuredPresets);

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

        $i = 0;
        foreach ($configuredPresets as $presetIdentifier => $variantPreset) {
            $queryBuilder
                ->orWhere(
                    $queryBuilder->expr()->andX()
                        ->add($queryBuilder->expr()->eq('iv.presetIdentifier', sprintf(':presetIdentifier_%d', $i)))
                        ->add($queryBuilder->expr()->notIn('iv.presetVariantName', array_keys($variantPreset->variants())))
                )
                ->setParameter(sprintf('presetIdentifier_%d', $i), $presetIdentifier);
            $i++;
        }

        return $queryBuilder->getQuery()->execute();
    }
}
